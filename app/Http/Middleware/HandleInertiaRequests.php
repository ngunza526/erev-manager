<?php

namespace App\Http\Middleware;

use App\Models\ExchangeRate;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $workspace = app(WorkspaceContext::class);
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only('id', 'name', 'email', 'level', 'status', 'church_id', 'community_id'),
                'space' => fn () => $workspace->space($user, $request),
                'space_label' => fn () => $workspace->spaceLabel($user, $request),
                'active_church_id' => fn () => $workspace->churchId($user, $request),
                'active_community_id' => fn () => $workspace->communityId($user, $request),
                'context_switcher' => fn () => $workspace->switcher($user, $request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'rdc' => [
                'default_exchange_rate' => fn () => (float) (ExchangeRate::query()
                    ->where('from_currency', 'USD')
                    ->where('to_currency', 'CDF')
                    ->latest('rated_at')
                    ->value('rate') ?? 1),
                'payment_methods' => [
                    'cash' => 'Caisse',
                    'bank' => 'Banque',
                    'card' => 'Carte bancaire',
                    'mobile_money' => 'Mobile Money',
                ],
            ],
        ];
    }
}
