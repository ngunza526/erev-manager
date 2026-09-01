<?php

namespace App\Http\Middleware;

use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspace
{
    public function __construct(private WorkspaceContext $context)
    {
    }

    public function handle(Request $request, Closure $next, string $space): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        abort_unless($this->context->space($user, $request) === $space, 403);

        return $next($request);
    }
}
