<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;

class CheckPermission
{
     public function handle($request, Closure $next, $permission)
     {
         $user = Auth::user();
         $role = $user->role; // Get the user's role
     
         // Check if the role has the required permission
         if ($role->permissions()->where('slug', $permission)->exists()) {
             return $next($request);
         }
     
         abort(403, 'Unauthorized');
     }
   
}
