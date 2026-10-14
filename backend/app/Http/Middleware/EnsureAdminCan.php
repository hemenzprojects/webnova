<?php

namespace App\Http\Middleware;

use App\Admin\Access;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "admin.can:{permission},{view|manage}" for API endpoints the
 * admin tools call (page editor, media library). Uses the admin's login session,
 * so the route also needs the "web" middleware group.
 */
class EnsureAdminCan
{
    public function handle(Request $request, Closure $next, string $permission, string $level = Access::VIEW): Response
    {
        $user = auth()->guard('web')->user();
        abort_unless($user, 401, 'Sign in to the admin first.');

        $allowed = $level === Access::MANAGE ? Access::canManage($permission) : Access::canView($permission);
        abort_unless($allowed, 403, 'Your role does not allow this.');

        return $next($request);
    }
}
