<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // ==================== HELPER METHOD ====================
    
    /**
     * Check if user is admin
     */
    private function checkAdmin($user)
    {
        return $user && $user->role === 'admin';
    }

    // ==================== ADMIN ONLY METHODS ====================

    /**
     * Get all users (Admin only)
     */
    public function index(Request $request)
    {
        // Check if user is admin - FIXED: using role check instead of isAdmin()
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        
        // Get filter parameters
        $role = $request->get('role');
        $search = $request->get('search');
        
        // Build query
        $query = User::select([
            'id',
            'first_name',
            'last_name',
            'email',
            'role',
            'first_login',
            'last_login',
            'created_at',
            'updated_at'
        ]);

        // Apply filters
        if ($role) {
            $query->where('role', $role);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Get paginated results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => [
                'role' => $role,
                'search' => $search,
            ]
        ]);
    }

    /**
     * Get single user (Admin can see all, users can only see themselves)
     */
    public function show(Request $request, $id)
    {
        $requestedUser = User::find($id);
        
        if (!$requestedUser) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        // Users can only view their own profile unless they're admin
        if (!$this->checkAdmin($request->user()) && $request->user()->id != $id) {
            return response()->json([
                'message' => 'Access denied. You can only view your own profile.'
            ], 403);
        }

        return response()->json([
            'user' => $this->formatUserResponse($requestedUser)
        ]);
    }

    /**
     * Update user (Admin can update anyone, users can only update themselves)
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Check authorization: users can update themselves, admin can update anyone
        if (!$this->checkAdmin($request->user()) && $request->user()->id != $user->id) {
            return response()->json([
                'message' => 'Unauthorized to update this user'
            ], 403);
        }

        // Validation rules
        $validationRules = [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
        ];

        // Only admin can change role
        if ($this->checkAdmin($request->user())) {
            $validationRules['role'] = ['sometimes', 'in:admin,user'];
        }

        // Only admin can reset password for other users
        if ($this->checkAdmin($request->user()) && $request->user()->id != $user->id) {
            $validationRules['password'] = ['sometimes', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($validationRules);

        // Handle password reset
        if (isset($validated['password']) && $this->checkAdmin($request->user())) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $this->formatUserResponse($user, false)
        ]);
    }

    /**
     * Delete user (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        // Only admin can delete users
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Prevent admin from deleting themselves
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => 'Cannot delete your own account.'
            ], 403);
        }

        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Delete user's tokens first
        $user->tokens()->delete();
        
        // Delete user
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'deleted_user' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
            ]
        ]);
    }

    /**
     * Change user role (Admin only)
     */
    public function changeRole(Request $request, $id)
    {
        // Only admin can change roles
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,user'],
        ]);

        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        // Prevent changing own role (safety measure)
        if ($request->user()->id == $id) {
            return response()->json([
                'message' => 'Cannot change your own role.'
            ], 403);
        }

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    // ==================== USER PROFILE METHODS ====================

    /**
     * Get current user's profile
     */
    public function showProfile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => $this->formatUserResponse($user)
        ]);
    }

    /**
     * Update current user's profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'current_password' => ['required_with:new_password'],
            'new_password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);
        
        // Handle password change if requested
        if (isset($validated['new_password'])) {
            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            
            $user->password = Hash::make($validated['new_password']);
            unset($validated['current_password']);
            unset($validated['new_password']);
        }
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $this->formatUserResponse($user, false)
        ]);
    }

    /**
     * Change current user's password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    // ==================== STATISTICS METHODS (ADMIN) ====================

    /**
     * Get user statistics (Admin only)
     */
    public function getStatistics(Request $request)
    {
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $userCount = User::where('role', 'user')->count();
        
        // Users created in last 7 days
        $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();
        
        // Active users (logged in within last 30 days)
        $activeUsers = User::where('last_login', '>=', now()->subDays(30))->count();

        return response()->json([
            'statistics' => [
                'total_users' => $totalUsers,
                'admin_count' => $adminCount,
                'user_count' => $userCount,
                'recent_users' => $recentUsers,
                'active_users' => $activeUsers,
                'admin_percentage' => $totalUsers > 0 ? round(($adminCount / $totalUsers) * 100, 2) : 0,
                'active_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
            ],
            'timeframe' => [
                'recent_days' => 7,
                'active_days' => 30,
            ]
        ]);
    }

    /**
     * Get user activity log (Admin only) - FIXED: Removed $id parameter
     */
    public function getUserActivity(Request $request)
    {
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $users = User::select([
            'id',
            'first_name',
            'last_name',
            'email',
            'role',
            'first_login',
            'last_login',
            'created_at'
        ])
        ->orderBy('last_login', 'desc')
        ->limit(50)
        ->get();

        return response()->json([
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'first_login' => $user->first_login,
                    'last_login' => $user->last_login,
                    'created_at' => $user->created_at,
                    'days_since_last_login' => $user->last_login 
                        ? now()->diffInDays($user->last_login) 
                        : null,
                    'account_age_days' => now()->diffInDays($user->created_at),
                ];
            })
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Format user response consistently
     */
    private function formatUserResponse(User $user, bool $includeAll = true): array
    {
        $response = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'first_login' => $user->first_login,
            'last_login' => $user->last_login,
        ];

        if ($includeAll) {
            $response['created_at'] = $user->created_at;
            $response['updated_at'] = $user->updated_at;
            $response['email_verified_at'] = $user->email_verified_at;
            $response['account_age'] = now()->diffForHumans($user->created_at, true);
            
            if ($user->last_login) {
                $response['last_active'] = now()->diffForHumans($user->last_login, true);
            }
        }

        return $response;
    }

    /**
     * Search users (Admin only, with advanced filters)
     */
    public function searchUsers(Request $request)
    {
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'search' => ['required', 'string', 'min:2'],
            'role' => ['sometimes', 'in:admin,user'],
            'sort_by' => ['sometimes', 'in:name,email,created_at,last_login'],
            'sort_order' => ['sometimes', 'in:asc,desc'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $search = $request->search;
        $role = $request->role;
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        $query = User::select([
            'id',
            'first_name',
            'last_name',
            'email',
            'role',
            'first_login',
            'last_login',
            'created_at'
        ]);

        // Apply search
        $query->where(function($q) use ($search) {
            $q->where('first_name', 'LIKE', "%{$search}%")
              ->orWhere('last_name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
        });

        // Apply role filter
        if ($role) {
            $query->where('role', $role);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'name':
                $query->orderBy('first_name', $sortOrder)
                      ->orderBy('last_name', $sortOrder);
                break;
            case 'email':
                $query->orderBy('email', $sortOrder);
                break;
            case 'last_login':
                $query->orderBy('last_login', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        $users = $query->paginate(20);

        return response()->json([
            'search_results' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
            'search_params' => [
                'search_term' => $search,
                'role_filter' => $role,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Bulk update user roles (Admin only)
     */
    public function bulkUpdateRoles(Request $request)
    {
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'role' => ['required', 'in:admin,user'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prevent admin from changing their own role
        if (in_array($request->user()->id, $request->user_ids)) {
            return response()->json([
                'message' => 'Cannot change your own role in bulk update.'
            ], 403);
        }

        $updatedCount = User::whereIn('id', $request->user_ids)
            ->update(['role' => $request->role]);

        return response()->json([
            'message' => "Successfully updated roles for {$updatedCount} users",
            'updated_count' => $updatedCount,
            'new_role' => $request->role,
        ]);
    }

    /**
     * Export users data (Admin only - CSV/JSON)
     */
    public function exportUsers(Request $request)
    {
        if (!$this->checkAdmin($request->user())) {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        $format = $request->get('format', 'json');
        $role = $request->get('role');

        $query = User::select([
            'id',
            'first_name',
            'last_name',
            'email',
            'role',
            'first_login',
            'last_login',
            'created_at',
            'updated_at'
        ]);

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->get();

        if ($format === 'csv') {
            $csvData = "ID,First Name,Last Name,Email,Role,First Login,Last Login,Created At,Updated At\n";
            
            foreach ($users as $user) {
                $csvData .= implode(',', [
                    $user->id,
                    '"' . str_replace('"', '""', $user->first_name) . '"',
                    '"' . str_replace('"', '""', $user->last_name) . '"',
                    '"' . str_replace('"', '""', $user->email) . '"',
                    $user->role,
                    $user->first_login,
                    $user->last_login,
                    $user->created_at,
                    $user->updated_at,
                ]) . "\n";
            }

            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
        }

        // Default JSON response
        return response()->json([
            'exported_at' => now()->toDateTimeString(),
            'total_users' => $users->count(),
            'format' => $format,
            'users' => $users,
        ]);
    }
}