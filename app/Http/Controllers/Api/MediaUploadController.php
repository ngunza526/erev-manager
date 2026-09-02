<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChurchMediaItem;
use App\Models\MediaUploadSession;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MediaUploadController extends Controller
{
    /**
     * SEC-22 — Liste blanche stricte : rien qui puisse etre execute ou rendu
     * comme du HTML depuis le disque public (pas de php/phtml/svg/html/js...).
     */
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        'mp3', 'm4a', 'aac', 'ogg', 'oga', 'wav', 'flac',
        'mp4', 'm4v', 'webm', 'mov',
        'pdf',
    ];

    private const ALLOWED_MEDIA_TYPES = ['image', 'audio', 'video', 'document'];

    /** Plafond de taille du corps base64 par morceau (~12 Mio decode). */
    private const MAX_CHUNK_BASE64_LENGTH = 16_000_000;

    public function initiate(Request $request, AccessScope $scope): JsonResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'title' => ['required', 'string', 'max:255'],
            'media_type' => ['required', 'string', Rule::in(self::ALLOWED_MEDIA_TYPES)],
            'category' => ['required', 'string', 'max:120'],
            'original_filename' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, callable $fail) {
                $extension = strtolower((string) pathinfo((string) $value, PATHINFO_EXTENSION));
                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    $fail('Type de fichier non autorise.');
                }
            }],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        $session = MediaUploadSession::create([
            ...$data,
            'user_id' => $request->user()?->id,
            'upload_id' => (string) Str::uuid(),
            'received_chunks' => [],
            'status' => 'initiated',
        ]);

        return response()->json(['data' => $this->payload($session)], 201);
    }

    public function show(Request $request, MediaUploadSession $upload, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $upload->church_id);

        return response()->json(['data' => $this->payload($upload)]);
    }

    public function chunk(Request $request, MediaUploadSession $upload, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $upload->church_id);

        if ($upload->status === 'completed') {
            throw ValidationException::withMessages(['upload' => 'Cet upload est deja finalise.']);
        }

        $data = $request->validate([
            'chunk_index' => ['required', 'integer', 'min:0'],
            'content_base64' => ['required', 'string', 'max:'.self::MAX_CHUNK_BASE64_LENGTH],
        ]);

        if ((int) $data['chunk_index'] >= $upload->total_chunks) {
            throw ValidationException::withMessages(['chunk_index' => 'Index de morceau hors limites.']);
        }

        $content = base64_decode($data['content_base64'], true);
        if ($content === false) {
            throw ValidationException::withMessages(['content_base64' => 'Contenu base64 invalide.']);
        }

        Storage::disk('local')->put($this->chunkPath($upload, (int) $data['chunk_index']), $content);

        $received = collect($upload->received_chunks ?? [])
            ->push((int) $data['chunk_index'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $upload->update([
            'received_chunks' => $received,
            'status' => count($received) === (int) $upload->total_chunks ? 'ready_to_complete' : 'uploading',
        ]);

        return response()->json(['data' => $this->payload($upload->fresh())]);
    }

    public function complete(Request $request, MediaUploadSession $upload, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $upload->church_id);

        $received = collect($upload->received_chunks ?? [])->sort()->values();
        if ($received->count() !== (int) $upload->total_chunks) {
            throw ValidationException::withMessages(['chunks' => 'Tous les morceaux ne sont pas encore recus.']);
        }

        $safeName = Str::slug(pathinfo($upload->original_filename, PATHINFO_FILENAME)) ?: 'media';
        $extension = strtolower((string) pathinfo($upload->original_filename, PATHINFO_EXTENSION));

        // SEC-22 : defense en profondeur, on refuse a l'ecriture toute extension
        // qui aurait echappe a la validation d'initiation.
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['upload' => 'Type de fichier non autorise.']);
        }

        $filename = $safeName.'-'.$upload->upload_id.'.'.$extension;
        $storagePath = 'church-media/'.$filename;

        $assembled = '';
        for ($index = 0; $index < (int) $upload->total_chunks; $index++) {
            $chunkPath = $this->chunkPath($upload, $index);
            if (! Storage::disk('local')->exists($chunkPath)) {
                throw ValidationException::withMessages(['chunks' => "Le morceau {$index} est manquant."]);
            }

            $assembled .= Storage::disk('local')->get($chunkPath);
        }

        Storage::disk('public')->put($storagePath, $assembled);
        Storage::disk('local')->deleteDirectory($this->chunkDirectory($upload));

        $url = Storage::disk('public')->url($storagePath);
        $upload->update([
            'status' => 'completed',
            'storage_path' => $storagePath,
            'storage_url' => $url,
        ]);

        $media = ChurchMediaItem::create([
            'church_id' => $upload->church_id,
            'title' => $upload->title,
            'media_type' => $upload->media_type,
            'category' => $upload->category,
            'storage_url' => $url,
            'copyright_status' => 'interne',
            'offline_available' => true,
            'status' => 'published',
            'notes' => 'Cree depuis upload mobile reprenable '.$upload->upload_id,
        ]);

        return response()->json([
            'data' => $this->payload($upload->fresh()),
            'media' => $media,
        ]);
    }

    private function payload(MediaUploadSession $upload): array
    {
        return [
            'id' => $upload->id,
            'upload_id' => $upload->upload_id,
            'church_id' => $upload->church_id,
            'title' => $upload->title,
            'media_type' => $upload->media_type,
            'category' => $upload->category,
            'original_filename' => $upload->original_filename,
            'total_chunks' => $upload->total_chunks,
            'received_chunks' => $upload->received_chunks ?? [],
            'status' => $upload->status,
            'storage_url' => $upload->storage_url,
        ];
    }

    private function chunkDirectory(MediaUploadSession $upload): string
    {
        return "media-upload-chunks/{$upload->upload_id}";
    }

    private function chunkPath(MediaUploadSession $upload, int $index): string
    {
        return $this->chunkDirectory($upload)."/{$index}.part";
    }
}
