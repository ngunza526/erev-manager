<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceContextController extends Controller
{
    public function update(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();

        abort_unless($context->canSwitch($user), 403);

        $data = $request->validate([
            'space' => ['required', Rule::in(['communaute', 'eglise'])],
            'community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        // Communaute d'attache : fixee pour un Administrateur ; nulle pour le
        // SuperAdmin plateforme, qui opere sur tous les locataires et choisit
        // librement la communaute ou l'eglise cible.
        $ownCommunityId = $context->communityId($user, $request);

        if ($data['space'] === 'communaute') {
            $communityId = $ownCommunityId ?: (int) ($data['community_id'] ?? 0);

            abort_if(! $communityId, 422, 'Choisissez une communaute.');
            abort_if(
                $ownCommunityId && ! empty($data['community_id']) && (int) $data['community_id'] !== $ownCommunityId,
                422,
                'Cette communaute est hors de votre perimetre.'
            );

            $request->session()->put('workspace.space', 'communaute');
            $request->session()->put('workspace.community_id', $communityId);
            $request->session()->forget('workspace.church_id');

            return redirect()->route('dashboard')->with('success', 'Contexte communaute active.');
        }

        $church = Church::whereKey((int) ($data['church_id'] ?? 0))
            ->when($ownCommunityId, fn ($query) => $query->where('community_id', $ownCommunityId))
            ->first();

        abort_unless($church, 422, 'Cette eglise est hors de votre perimetre.');

        $request->session()->put('workspace.space', 'eglise');
        $request->session()->put('workspace.community_id', $church->community_id);
        $request->session()->put('workspace.church_id', $church->id);

        return redirect()->route('dashboard')->with('success', 'Contexte eglise active.');
    }
}
