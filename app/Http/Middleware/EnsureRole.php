<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Foydalanish: ->middleware('role:client') yoki 'role:moderator,admin'. Bloklangan akkaunt ham to'xtatiladi. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(fn (string $r) => UserRole::from($r), $roles);

        if ($user === null || ! $user->is_active || ! in_array($user->role, $allowed, true)) {
            throw new AuthorizationException('Bu bo\'limga kirish huquqingiz yo\'q.');
        }

        return $next($request);
    }
}
