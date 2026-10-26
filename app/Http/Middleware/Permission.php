<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Permission
{
    // Admin-panel areas a seller (role "user") must never reach, whatever other permissions say
    private const ADMIN_ONLY_PREFIXES = [
        'admin_dashboard', 'general_setting', 'shipping_company', 'cod_setting', 'whatsapp_setting',
        'status_mapping', 'zipcode', 'clients', 'client_kyc_request', 'ticket_request', 'roles', 'staff',
    ];

    public function handle(Request $request, Closure $next, $permission = null)
    {
        // This also runs on every web request without a permission (login page etc.);
        // only routes that name a permission require a logged-in user
        if (!empty($permission) && !Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'msg' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        if (!empty($permission) && Auth::user()->role === 'user'
            && in_array(strtok($permission, '.'), self::ADMIN_ONLY_PREFIXES, true)) {
            return abort(403);
        }

        if (Auth::check())
		{ 
			$admin = Auth::user();
			$role = $admin->role;

			$hasAdminDashboard = $admin->rolePermissions()->where('name', 'admin_dashboard.view')->exists();
			$isFullAccessRole = in_array($role, ['master_admin', 'admin'], true) || $admin->id == 1 || $hasAdminDashboard;

			// If a specific permission is required
			if (!empty($permission)) {
				$hasPermission = $admin->rolePermissions()->where('name', $permission)->exists();
				if (!$isFullAccessRole && $role !== 'user' && !$hasPermission) {
					return abort(403);
				}
			}

			if ($role !== 'user') 
			{ 
				if ($isFullAccessRole) {
					$permissions = DB::table('permissions')->where('status', 1)->pluck('name');
					$actions = ['view', 'add', 'edit', 'delete'];

					foreach ($permissions as $permName) {
						foreach ($actions as $action) {
							Config::set("permission.{$permName}.{$action}", true);
						}
					}
				} else {
					$permissions = $admin->rolePermissions()->get(['name', 'value']);
					foreach ($permissions as $perm) {
						Config::set('permission.' . $perm->name, $perm->value);
					}
				}
			}
		}
        return $next($request);
    }
}
