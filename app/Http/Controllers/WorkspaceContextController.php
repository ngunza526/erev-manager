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

        $communityId = $context->communityId($user, $request);
        abort_if(! $communityId, 422, 'Aucune communaute de coordination disponible pour ce SuperAdmin.');

        if ($data['space'] === 'communaute') {
            abort_if(! empty($data['community_id']) && (int) $data['community_id'] !== $communityId, 422, 'Cette communaute est hors de votre perimetre.');

            $request->session()->put('workspace.space', 'communaute');
            $request->session()->put('workspace.community_id', $communityId);
            $request->session()->forget('workspace.church_id');

            return redirect()->route('dashboard')->with('success', 'Contexte communaute active.');
        }

        $churchId = (int) ($data['church_id'] ?? 0);
        $church = Church::whereKey($churchId)->where('community_id', $communityId)->first();

        abort_unless($church, 422, 'Cette eglise est hors de votre perimetre.');

        $request->session()->put('workspace.space', 'eglise');
        $request->session()->put('workspace.community_id', $communityId);
        $request->session()->put('workspace.church_id', $church->id);

        return redirect()->route('dashboard')->with('success', 'Contexte eglise active.');
    }
}
