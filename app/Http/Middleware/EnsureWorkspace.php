<?php

namespace App\Http\Middleware;

use App\Services\WorkspaceContext;
use App\Support\Rbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspace
{
    public function __construct(private WorkspaceContext $context) {}

    public function handle(Request $request, Closure $next, string $space): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        // Le role technique plateforme (exploitant du SaaS) n'est pas soumis a
        // la separation d'espaces communaute / eglise.
        if ($user->level === Rbac::LEVEL_PLATFORM) {
            return $next($request);
        }

        abort_unless($this->context->space($user, $request) === $space, 403);

        return $next($request);
    }
}
