<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Permission
{ 
    public function handle(Request $request, Closure $next, $permission = null)
    {
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
