<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RoleGroup;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Validator, DB, Auth, ImageManager, Hash, Helper;
use App\Services\MasterService;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class StaffController extends Controller
{
	protected $masterService;
	public function __construct()
	{
		$this->middleware('auth');
		// Staff and roles are managed by admins and franchises, never by sellers
		$this->middleware(function ($request, $next) {
			if (auth()->user()->role === 'user') {
				abort(403);
			}
			return $next($request);
		});
		$this->masterService = new MasterService();
	}

	public function roles()
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}
		return view('roles.index');
	}

	public function rolesAjax(Request $request)
	{
		if ($request->ajax()) {
			$authUser = auth()->user();
			$columns = ['id', 'name', 'status', 'created_at', 'id'];

			$search = $request->input('search.value');
			$start = $request->input('start');
			$limit = $request->input('length');

			// Base query
			$query = Role::query();
			$accessibleOwnerIds = $this->getAccessibleRoleOwnerIds($authUser);
			if (!empty($accessibleOwnerIds)) {
				$query->whereIn('user_id', $accessibleOwnerIds);
			}

			// Apply search filter if present
			if (!empty($search)) {
				$query->where(function ($q) use ($search) {
					$q->where('name', 'LIKE', "%{$search}%")
						->orWhere('created_at', 'LIKE', "%{$search}%");
				});
			}

			$totalData = $query->count();
			$totalFiltered = $totalData;

			// Get data with limit and offset for pagination
			$values = $query->offset($start)->limit($limit)
				->orderBy($columns[$request->input('order.0.column')], $request->input('order.0.dir'))
				->get();

			// Format response
			$data = [];
			$i = $start + 1;
			foreach ($values as $key => $value) {
				$statusClass = $value->status == 1 ? 'success' : 'danger';
				$statusText = $value->status == 1 ? 'Active' : 'In-Active';
				$isMasterAdminRole = strtolower(str_replace('_', ' ', trim($value->name))) === 'master admin';

				$appendData = [
					'id' => $i, // Use $key instead of manually tracking $i
					'name' => ucwords(str_replace('_', ' ', $value->name)),
					'status' => "<span class=\"badge bg-{$statusClass}\">{$statusText}</span>",
					'created_at' => $value->created_at->format('Y-m-d H:i:s'),
					'action' => '',
				];

				$actions = [];
				if (!$isMasterAdminRole && (config('permission.roles.edit') || ($authUser && ($authUser->isMasterAdmin())))) {
					$actions[] = "<a href=\"" . route('roles.edit', ['id' => $value->id]) . "\" onclick=\"editRoles(this, event)\" class=\"btn btn-icon waves-effect waves-light action-icon mr-1\"><i class='mdi mdi-pencil'></i></a>";
				}
				if ((config('permission.roles.delete') || ($authUser && ($authUser->isMasterAdmin()))) && !$isMasterAdminRole) {
					$actions[] = "<a href=\"" . route('roles.delete', ['id' => $value->id]) . "\" onclick=\"deleteRecord(this, event)\" class=\"btn btn-icon waves-effect waves-light action-icon\"> <i class='mdi mdi-trash-can-outline'></i></a>";
				}
				$appendData['action'] = implode(' ', $actions);

				$data[] = $appendData;
				$i++;
			}

			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => $totalData,
				'recordsFiltered' => $totalFiltered,
				'data' => $data,
			]);
		}
	}

	public function rolesCreate()
	{
		$permissions = Permission::where('status', 1)
			->orderBy('position', 'asc')
			->get();

		$view = view('roles.create', compact('permissions'))->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function rolesStore(Request $request)
	{
		$request->merge([
			'name' => strtolower(str_replace(' ', '_', trim($request->name)))
		]);

		$validator = Validator::make($request->all(), [
			'name' => [
				'required',
				'string',
				'unique:roles,name'
			],
			'status' => [
				'required',
				'integer',
				'in:0,1'
			]
		]);


		if ($validator->fails()) {
			return response()->json(['status' => 'validation', 'errors' => $validator->errors()]);
		}

		try {
			DB::beginTransaction();

			if (!$request->permission) {
				return response()->json(['status' => 'error', 'msg' => 'You have not select any checkbox.']);
			}
			$admin = auth()->user();

			$currentTime = now();
			$data = $request->except('_token', 'permission');
			$data['user_id'] = $admin->id;
			$data['created_at'] = $currentTime;
			$data['updated_at'] = $currentTime;

			$role = Role::create($data);

			$permissions = Arr::dot($request->permission ?? []);

			// Prepare data for bulk insert
			$data = [];
			foreach ($permissions as $key => $permission) {
				$data[] = [
					'role_id' => $role->id,
					'name' => $key,
					'value' => $permission,
					'created_at' => $currentTime,
					'updated_at' => $currentTime,
				];
			}

			// Chunk size for batch insertion (adjust as needed)
			$chunkSize = 200;
			$dataChunks = array_chunk($data, $chunkSize);
			foreach ($dataChunks as $chunk) {
				// Bulk insert using insert method
				RoleGroup::insert($chunk);
			}

			DB::commit();

			return response()->json(['status' => 'success', 'msg' => 'The role has been created successfully.']);
		}
		catch (\Throwable $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to update settings. ' . $e->getMessage()]);
		}
	}

	public function rolesEdit($roleId)
	{
		$role = Role::find($roleId);
		if (!$role) {
			return response()->json(['status' => 'error', 'msg' => 'Role not found.']);
		}
		if (!$this->canAccessRole(auth()->user(), $role)) {
			return response()->json(['status' => 'error', 'msg' => 'You are not allowed to access this role.'], 403);
		}

		$permissions = Permission::where('status', 1)
			->orderBy('position', 'asc')
			->get();

		$roleper = RoleGroup::where('role_id', $roleId)
			->pluck('name')
			->toArray();

		$view = view('roles.edit', compact('permissions', 'role', 'roleper'))->render();
		return response()->json(['status' => 'success', 'view' => $view]);
	}

	public function rolesUpdate(Request $request, $id)
	{
		$request->merge([
			'name' => strtolower(str_replace(' ', '_', trim($request->name)))
		]);

		// Validate input
		$validator = Validator::make($request->all(), [
			'name' => [
				'required',
				'string',
				'unique:roles,name,' . $id
			],
			'status' => [
				'required',
				'integer',
				'in:0,1'
			]
		]);

		// Return validation errors if validation fails
		if ($validator->fails()) {
			return response()->json(['status' => 'validation', 'errors' => $validator->errors()]);
		}

		try {
			DB::beginTransaction();

			// Find the role by ID
			$role = Role::find($id);
			if (!$role) {
				return response()->json(['status' => 'error', 'msg' => 'Role not found.']);
			}
			if (!$this->canAccessRole(auth()->user(), $role)) {
				return response()->json(['status' => 'error', 'msg' => 'You are not allowed to update this role.'], 403);
			}

			// Ensure at least one permission is provided
			if (!$request->permission) {
				return response()->json(['status' => 'error', 'msg' => 'You have not selected any permissions.']);
			}

			// Get the current authenticated admin
			$admin = auth()->user();

			// Prepare data for updating the role
			$currentTime = now();
			$data = $request->except('_token', 'permission', 'old_name');
			$data['user_id'] = $admin->id;
			$data['updated_at'] = $currentTime;

			// Update the role
			$role->update($data);

			User::whereRole($request->old_name)->update(['role' => $request->name]);

			// Fetch users and permissions
			$users = User::whereRole($request->name)->get();
			$permissions = Arr::dot($request->permission ?? []);
			$permissionNames = array_keys($permissions);

			// Delete old role permissions not in current permissions
			RolePermission::whereIn('user_id', $users->pluck('id'))
				->whereNotIn('name', $permissionNames)
				->delete();

			// Delete old role groups
			RoleGroup::whereRole_id($id)->delete();

			// Bulk insert role groups and role permissions
			$roleGroups = [];
			$rolePermissions = [];

			foreach ($permissions as $key => $permission) {
				$roleGroups[] = [
					'role_id' => $id,
					'name' => $key,
					'value' => $permission,
					'created_at' => $currentTime,
					'updated_at' => $currentTime
				];

				foreach ($users as $user) {
					// Check if role permission exists for user
					$exists = RolePermission::where('user_id', $user->id)
						->where('name', $key)
						->exists();

					if (!$exists) {
						$rolePermissions[] = [
							'user_id' => $user->id,
							'name' => $key,
							'value' => $permission,
							'created_at' => $currentTime,
							'updated_at' => $currentTime
						];
					}
				}
			}

			// Chunk size for bulk insert operations (adjust as needed)
			$chunkSize = 100; // Example chunk size

			// Insert role groups in chunks
			foreach (array_chunk($roleGroups, $chunkSize) as $chunk) {
				RoleGroup::insert($chunk);
			}

			// Insert role permissions in chunks
			foreach (array_chunk($rolePermissions, $chunkSize) as $chunk) {
				RolePermission::insert($chunk);
			}

			// Commit the transaction
			DB::commit();

			// Return success response
			return response()->json(['status' => 'success', 'msg' => 'The role has been updated successfully.']);

		}
		catch (\Throwable $e) {
			DB::rollBack();
			return response()->json(['status' => 'error', 'msg' => 'Failed to update the role. ' . $e->getMessage()]);
		}
	}

	public function rolesDelete($id)
	{
		try {
			DB::beginTransaction();

			$role = Role::find($id);
			if (!$role) {
				return redirect()->back()->with('error', 'The role was not found.');
			}
			if (!$this->canAccessRole(auth()->user(), $role)) {
				DB::rollBack();
				return redirect()->back()->with('error', 'You are not allowed to delete this role.');
			}

			// Ensure roleGroups() relationship exists in Role model
			if ($role->roleGroups()->count()) {
				$role->roleGroups()->delete();
			}

			$role->delete();

			DB::commit();
			return redirect()->back()->with('success', 'Role and its associated role groups deleted successfully.');
		}
		catch (\Exception $e) {
			DB::rollBack();
			return redirect()->back()->with('error', 'Failed to delete the role. ' . $e->getMessage());
		}
	}

	public function staff()
	{
		if (auth()->user()->role === 'user') {
			abort(403, 'Permission denied');
		}
		return view('staff.index');
	}

	public function staffAjax(Request $request)
	{
		if ($request->ajax()) {
			$authUser = auth()->user();
			$columns = ['id', 'company_name', 'name', 'mobile', 'email', 'role', 'status', 'created_at', 'id'];

			$search = $request->input('search.value');
			$start = $request->input('start');
			$limit = $request->input('length');

			// Base query
			$query = User::query()->whereNotIn('role', ['user']);


			// Apply search filter if present
			if (!empty($search)) {
				$query->where(function ($q) use ($search) {
					$q->where('name', 'LIKE', "%{$search}%")
						->orwhere('company_name', 'LIKE', "%{$search}%")
						->orwhere('email', 'LIKE', "%{$search}%")
						->orwhere('mobile', 'LIKE', "%{$search}%")
						->orwhere('role', 'LIKE', "%{$search}%")
						->orWhere('created_at', 'LIKE', "%{$search}%");
				});
			}

			$totalData = $query->count();
			$totalFiltered = $totalData;

			// Get data with limit and offset for pagination
			$values = $query->offset($start)->limit($limit)
				->orderBy($columns[$request->input('order.0.column')], $request->input('order.0.dir'))
				->get();

			// Format response
			$data = [];
			$i = $start + 1;
			foreach ($values as $key => $value) {
				$isMasterAdminRow = strtolower((string) $value->role) === 'master_admin';
				// Build the data row
				$appendData = [
					'id' => $i,
					'company_name' => $value->company_name,
					'name' => $value->name,
					'mobile' => $value->mobile,
					'email' => $value->email,
					'role' => ucwords(str_replace('_', ' ', $value->role)),
					'status' => '<span class="badge bg-' . ($value->status == 1 ? 'success' : 'danger') . '">' . ($value->status == 1 ? 'Active' : 'In-Active') . '</span>',
					'created_at' => $value->created_at->format('Y-m-d H:i:s'),
					'action' => '',
				];

				$actions = [];
				if (!$isMasterAdminRow && (config('permission.staff.edit') || ($authUser && ($authUser->isMasterAdmin())))) {
					$actions[] = '<a href="' . route('staff.edit', ['id' => $value->id]) . '" onclick="editStaff(this, event)"><i class="mdi mdi-pencil class-pencil"></i></a>';
				}
				// Delete permission
				if ((config('permission.staff.delete') || ($authUser && ($authUser->isMasterAdmin()))) && !$isMasterAdminRow) {
					$actions[] = '<a href="' . route('staff.delete', ['id' => $value->id]) . '" onclick="deleteRecord(this, event)" ><i class="mdi mdi-trash-can-outline class-pencil"></i></a>';
				}
				if (!$isMasterAdminRow && (config('permission.staff.edit') || ($authUser && ($authUser->isMasterAdmin())))) {
					$actions[] = '<a href="' . route('staff.permission', ['id' => $value->id]) . '" onclick="editPermission(this, event)" ><i class="mdi mdi-lock class-pencil"></i></a>';
				}

				// Combine actions into the action column
				$appendData['action'] = implode(' ', $actions);

				// Append data row to output
				$data[] = $appendData;
				$i++;
			}


			return response()->json([
				'draw' => intval($request->input('draw')),
				'recordsTotal' => $totalData,
				'recordsFiltered' => $totalFiltered,
				'data' => $data,
			]);
		}
	}

	public function staffCreate()
	{
		$roles = $this->masterService->getRoles(1);
		$authUser = auth()->user();
		if ($authUser && !$authUser->isMasterAdmin()) {
			$roles = $roles->reject(function ($role) {
				return in_array($role->name, ['admin', 'master_admin'], true);
			})->values();
		}
		$b2cRateCards = \App\Models\RateCard::where('group_name', '!=', 'Lyle Snyder')
			->where('rate_type', 'b2c')
			->orderBy('group_name')
			->get();
		$b2bRateCards = \App\Models\RateCard::where('group_name', '!=', 'Lyle Snyder')
			->where('rate_type', 'b2b')
			->orderBy('group_name')
			->get();
		$shippingApiProviders = $this->getStaffShippingApiProviders();
		$view = view('staff.create', compact('roles', 'b2cRateCards', 'b2bRateCards', 'shippingApiProviders'))->render();
		return response()->json([
			'status' => 'success',
			'view' => $view
		]);
	}

	public function rolesGroups($roleId)
	{
		// Fetch permissions, ordered by heading_position and position, and group them by heading
		$allPermissions = Permission::where('status', 1)
			->orderBy('position', 'asc')
			->get();

		// Fetch role permissions and map to names
		$roleper = RoleGroup::where('role_id', $roleId)
			->pluck('name')
			->toArray();

		$grantedModules = [];
		foreach ($roleper as $rp) {
			$moduleName = explode('.', $rp)[0];
			$grantedModules[$moduleName] = true;
		}

		$permissions = [];
		foreach ($allPermissions as $perm) {
			$expectedModuleName = strtolower(str_replace(' ', '_', $perm->name));
			if (isset($grantedModules[$expectedModuleName])) {
				$permissions[] = $perm;
			}
		}

		// Render the view and return the JSON response
		$view = view('roles.role-group', compact('permissions', 'roleper'))->render();

		return response()->json([
			'status' => 'success',
			'view' => $view
		]);
	}

	public function staffStore(Request $request)
	{
		$request->merge([
			'role' => strtolower(str_replace(' ', '_', trim($request->role)))
		]);

		$validator = Validator::make($request->all(), [
			'name' => 'required|string',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|string',
			'mobile' => 'required|string',
			'role' => 'required|string',
			'status' => 'required|in:1,0',
			'b2c_rate_card_id' => 'required',
			'b2b_rate_card_id' => 'nullable',
			'shipping_api_provider' => 'nullable|string|in:routxmitra'
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'validation',
				'errors' => $validator->errors()
			]);
		}

		try {
			DB::beginTransaction();
			$authUser = auth()->user();

			if (in_array($request->role, ['admin', 'master_admin'], true) && !$this->canManagePrivilegedRoles($authUser)) {
				return response()->json([
					'status' => 'error',
					'msg' => 'Only master admin can create admin or master admin users.'
				]);
			}

			$admin = auth()->user();

			$currentTimestamp = Carbon::now();

			$data = $request->except('_token', 'permission', 'password');
			$b2cRateCardId = (int) $request->input('b2c_rate_card_id', 0);
			$b2bRateCardId = (int) $request->input('b2b_rate_card_id', 0);

			if ($b2cRateCardId !== 0 && !\App\Models\RateCard::where('id', $b2cRateCardId)->where('rate_type', 'b2c')->exists()) {
				return response()->json([
					'status' => 'validation',
					'errors' => ['b2c_rate_card_id' => ['Please select a valid B2C rate card.']]
				]);
			}

			if ($b2bRateCardId !== 0 && !\App\Models\RateCard::where('id', $b2bRateCardId)->where('rate_type', 'b2b')->exists()) {
				return response()->json([
					'status' => 'validation',
					'errors' => ['b2b_rate_card_id' => ['Please select a valid B2B rate card.']]
				]);
			}

			$data['b2c_rate_card_id'] = $b2cRateCardId;
			$data['b2b_rate_card_id'] = $b2bRateCardId;
			$data['rate_card_id'] = $b2cRateCardId;
			$chargeRole = in_array($request->role, ['staff'], true);

			$data['shipping_api_provider'] = null;
			$data['password'] = Hash::make($request->password);
			$data['xpass'] = $request->password; // stored encrypted (User casts xpass)
			$data['email'] = strtolower($request->email);
			$data['staff_id'] = $admin->id;
			$data['created_at'] = $currentTimestamp;
			$data['updated_at'] = $currentTimestamp;

			$staff = User::create($data);

			$permissions = Arr::dot($request->permission ?? []);

			// Prepare data for bulk insert
			$permissionData = [];
			foreach ($permissions as $key => $permission) {
				$permissionData[] = [
					'user_id' => $staff->id,
					'name' => $key,
					'value' => $permission,
					'created_at' => $currentTimestamp,
					'updated_at' => $currentTimestamp,
				];
			}

			// Use chunking for bulk insert to improve performance
			$chunks = array_chunk($permissionData, 200); // Chunk size can be adjusted based on your needs

			foreach ($chunks as $chunk) {
				RolePermission::insert($chunk);
			}

			DB::commit();
			return response()->json([
				'status' => 'success',
				'msg' => 'The staff has been created successfully.'
			]);
		}
		catch (\Throwable $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to update settings. ' . $e->getMessage()
			]);
		}
	}

	public function staffEdit($staffId)
	{
		$staff = User::find($staffId);
		if (!$staff) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Staff not found.'
			]);
		}
		$authUser = auth()->user();
		if (!$this->canManageStaffMember($authUser, $staff)) {
			return response()->json([
				'status' => 'error',
				'msg' => 'You are not allowed to edit this staff member.'
			]);
		}

		$roles = $this->masterService->getRoles(1);
		$authUser = auth()->user();
		if ($authUser && !$authUser->isMasterAdmin()) {
			$roles = $roles->reject(function ($role) {
				return in_array($role->name, ['admin', 'master_admin'], true);
			})->values();
		}

		$b2cRateCards = \App\Models\RateCard::where('group_name', '!=', 'Lyle Snyder')
			->where('rate_type', 'b2c')
			->orderBy('group_name')
			->get();
		$b2bRateCards = \App\Models\RateCard::where('group_name', '!=', 'Lyle Snyder')
			->where('rate_type', 'b2b')
			->orderBy('group_name')
			->get();
		$shippingApiProviders = $this->getStaffShippingApiProviders();

		$view = view('staff.edit', compact('staff', 'roles', 'b2cRateCards', 'b2bRateCards', 'shippingApiProviders'))->render();
		return response()->json([
			'status' => 'success',
			'view' => $view
		]);
	}

	public function staffUpdate(Request $request, $staffId)
	{
		$request->merge([
			'role' => strtolower(str_replace(' ', '_', trim($request->role)))
		]);

		$validator = Validator::make($request->all(), [
			'name' => 'required|string',
			'email' => 'required|email|unique:users,email,' . $staffId,
			'password' => 'nullable|string',
			'mobile' => 'required|string',
			'role' => 'required|string',
			'status' => 'required|in:1,0',
			'b2c_rate_card_id' => 'required',
			'b2b_rate_card_id' => 'nullable',
			'shipping_api_provider' => 'nullable|string|in:routxmitra'
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'validation',
				'errors' => $validator->errors()
			]);
		}

		try {
			DB::beginTransaction();

			// Fetch the staff member to update
			$staff = User::findOrFail($staffId);
			$authUser = auth()->user();
			if (!$this->canManageStaffMember($authUser, $staff)) {
				DB::rollBack();
				return response()->json([
					'status' => 'error',
					'msg' => 'You are not allowed to update this staff member.'
				]);
			}

			// Same rule as staffStore: only privileged users may hand out admin roles
			if (in_array($request->role, ['admin', 'master_admin'], true) && !$this->canManagePrivilegedRoles($authUser)) {
				DB::rollBack();
				return response()->json([
					'status' => 'error',
					'msg' => 'You are not allowed to assign this role.'
				]);
			}

			$admin = auth()->user();
			$currentTimestamp = Carbon::now();

			// Update only the fields provided in the request; wallet and tokens never come from here
			$data = $request->except('_token', 'password', 'wallet_amount', 'api_token', 'xpass');
			$b2cRateCardId = (int) $request->input('b2c_rate_card_id', 0);
			$b2bRateCardId = (int) $request->input('b2b_rate_card_id', 0);

			if ($b2cRateCardId !== 0 && !\App\Models\RateCard::where('id', $b2cRateCardId)->where('rate_type', 'b2c')->exists()) {
				return response()->json([
					'status' => 'validation',
					'errors' => ['b2c_rate_card_id' => ['Please select a valid B2C rate card.']]
				]);
			}

			if ($b2bRateCardId !== 0 && !\App\Models\RateCard::where('id', $b2bRateCardId)->where('rate_type', 'b2b')->exists()) {
				return response()->json([
					'status' => 'validation',
					'errors' => ['b2b_rate_card_id' => ['Please select a valid B2B rate card.']]
				]);
			}

			$data['b2c_rate_card_id'] = $b2cRateCardId;
			$data['b2b_rate_card_id'] = $b2bRateCardId;
			$data['rate_card_id'] = $b2cRateCardId;
		
			if ($request->filled('password')) {
				$data['password'] = Hash::make($request->password); // Hash the new password if provided
				$data['xpass'] = $request->password; // stored encrypted (User casts xpass)
			}
			$data['email'] = strtolower($request->email);
			$data['staff_id'] = $admin->id;
			$data['updated_at'] = $currentTimestamp; // Update the timestamp

			// Update the staff details
			$staff->update($data);

			DB::commit();
			return response()->json([
				'status' => 'success',
				'msg' => 'The staff details have been updated successfully.'
			]);
		}
		catch (\Throwable $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to update staff details. ' . $e->getMessage()
			]);
		}
	}

	public function staffPermission($staffId)
	{
		$staff = User::find($staffId);
		if (!$staff) {
			return response()->json([
				'status' => 'error',
				'msg' => 'Staff not found.'
			]);
		}

		$roles = $this->masterService->getRoles(1);
		$authUser = auth()->user();
		if ($authUser && !$authUser->isMasterAdmin()) {
			$roles = $roles->reject(function ($role) {
				return in_array($role->name, ['admin', 'master_admin'], true);
			})->values();
		}

		// Fetch permissions in a single query and organize them by heading
		$allPermissions = Permission::whereStatus(1)
			->orderBy('position')
			->get();

		// Retrieve role permissions for the user
		$roleper = RolePermission::whereUser_id($staffId)
			->pluck('name')
			->toArray();

		// Retrieve base role permissions for their role
		$searchRole = strtolower(str_replace(' ', '_', $staff->role));
		$role = Role::whereRaw("LOWER(REPLACE(name, ' ', '_')) = ?", [$searchRole])->first();
		$grantedModules = [];
		if ($role) {
			$baseRolePer = RoleGroup::where('role_id', $role->id)->pluck('name')->toArray();
			foreach ($baseRolePer as $rp) {
				$moduleName = explode('.', $rp)[0];
				$grantedModules[$moduleName] = true;
			}
		}

		$permissions = [];
		foreach ($allPermissions as $perm) {
			$expectedModuleName = strtolower(str_replace(' ', '_', $perm->name));
			// Only show the module if the role gave it to them
			// Wait, what if the role is master_admin and has everything directly without RoleGroup?
			// Since master_admin has everything and bypasses this page usually, this is safe for admin & staff.
			if (isset($grantedModules[$expectedModuleName])) {
				$permissions[] = $perm;
			}
		}

		if (empty($roleper) && isset($baseRolePer)) {
			$roleper = $baseRolePer;
		}

		$view = view('staff.permission', compact('staff', 'roleper', 'permissions', 'roles'))->render();
		return response()->json([
			'status' => 'success',
			'view' => $view
		]);
	}

	public function staffPermissionUpdate(Request $request, $staffId)
	{
		$request->merge([
			'role' => strtolower(str_replace(' ', '_', trim($request->role)))
		]);

		$validator = Validator::make($request->all(), [
			'role' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => 'validation',
				'errors' => $validator->errors()
			]);
		}

		try {
			DB::beginTransaction();
			$authUser = auth()->user();
			$targetUser = User::findOrFail($staffId);

			if (
				!$this->canManageStaffMember($authUser, $targetUser)
				|| (
					in_array($request->role, ['admin', 'master_admin'], true)
					&& !$this->canAssignStaffRole($authUser, $request->role)
				)
			) {
				return response()->json([
					'status' => 'error',
					'msg' => 'You are not allowed to modify this staff member.'
				]);
			}

			$currentTime = now();
			$permissions = Arr::dot($request->permission ?? []);

			// Delete permissions not included in the current request
			RolePermission::where('user_id', $staffId)->delete();

			// Prepare permissionData for bulk insert
			$permissionData = [];
			foreach ($permissions as $key => $permission) {
				$permissionData[] = [
					'user_id' => $staffId,
					'name' => $key,
					'value' => $permission,
					'created_at' => $currentTime,
					'updated_at' => $currentTime,
				];
			}

			// Use chunking for bulk insert to improve performance
			$chunks = array_chunk($permissionData, 200); // Chunk size can be adjusted based on your needs

			foreach ($chunks as $chunk) {
				RolePermission::insert($chunk);
			}

			User::whereId($staffId)->update($request->only('role'));

			DB::commit();
			return response()->json([
				'status' => 'success',
				'msg' => 'The staff permission been updated successfully.'
			]);
		}
		catch (\Throwable $e) {
			DB::rollBack();
			return response()->json([
				'status' => 'error',
				'msg' => 'Failed to update staff details. ' . $e->getMessage()
			]);
		}
	}

	public function staffDelete($staffId)
	{
		try {
			DB::beginTransaction();

			$staff = User::findOrFail($staffId);
			$authUser = auth()->user();
			if (!$this->canManageStaffMember($authUser, $staff)) {
				return redirect()->back()->with('error', 'Only master admin can delete admin or master admin users.');
			}

			RolePermission::whereUser_id($staffId)->delete();
			$staff->delete();

			DB::commit();

			return redirect()->back()->with('success', 'The staff has been successfully deleted.');
		}
		catch (\Throwable $e) {
			DB::rollBack();
			return redirect()->back()->with('error', 'Failed to update staff details. ' . $e->getMessage());
		}
	}

	private function canManagePrivilegedRoles($authUser): bool
	{
		if (!$authUser) {
			return false;
		}

		if ($authUser->isMasterAdmin()) {
			return true;
		}

		$hasMasterAdmin = User::where('role', 'master_admin')->exists();
		return !$hasMasterAdmin && $authUser->isAdminLevel();
	}

	private function canAccessRole($authUser, $role): bool
	{
		if (!$authUser || !$role) {
			return false;
		}

		if ($authUser->isMasterAdmin()) {
			return true;
		}

		if ($authUser->role === 'staff') {
			return in_array((int) $role->user_id, $this->getAccessibleRoleOwnerIds($authUser), true);
		}

		return true;
	}

	private function getAccessibleRoleOwnerIds($authUser): array
	{
		if (!$authUser || $authUser->isMasterAdmin()) {
			return [];
		}

		if ($authUser->role === 'staff') {
			$creator = $authUser->createdBy;
			if ($creator && $creator->role === 'franchise') {
				return [(int) $creator->id];
			}
		}

		return [];
	}

	private function canManageStaffMember($authUser, $staff): bool
	{
		if (!$authUser || !$staff) {
			return false;
		}

		if ($authUser->isMasterAdmin()) {
			return true;
		}

		if ($staff->role === 'master_admin') {
			return false;
		}

		if ($staff->role === 'admin') {
			return $authUser->role === 'admin';
		}

		return true;
	}

	private function canAssignStaffRole($authUser, string $role): bool
	{
		if (!$authUser) {
			return false;
		}

		if ($role === 'master_admin') {
			return $authUser->isMasterAdmin();
		}

		if ($role === 'admin') {
			return $authUser->isAdminLevel();
		}

		return true;
	}

	private function getStaffShippingApiProviders(): array
	{
		return [
			'routxmitra' => 'RoutexMitra',
		];
	}
}
