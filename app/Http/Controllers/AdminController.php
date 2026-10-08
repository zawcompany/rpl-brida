<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsForEditor;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller tipis: validasi (FormRequest) -> AdminService -> respons.
 * Akses dijaga middleware auth + role:admin pada grup rute.
 */
class AdminController extends Controller
{
    use RespondsForEditor; // tableResponse() & perform() (DomainException -> 422)

    private const FILTER_KEYS = ['search', 'role', 'per_page'];

    public function __construct(private readonly AdminService $adminService)
    {
    }

    // ------------------------------------------------------------------ Dashboard

    public function dashboard(): View
    {
        return view('roles.admin.dashboard', [
            'stats' => $this->adminService->getDashboardStats(),
            'logs'  => $this->adminService->getRecentLogs(),
        ]);
    }

    // ------------------------------------------------------------------ Kelola Pengguna

    public function users(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.admin.users',
            'roles.admin.partials.user-table-rows',
            $this->adminService->getUsers($request->only(self::FILTER_KEYS)),
            ['roleOptions' => User::ROLE_LABELS, 'initialRole' => (string) $request->query('role', '')]
        );
    }

    public function showUser(User $user): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($user)]);
    }

    public function storeUser(StoreUserRequest $request): JsonResponse
    {
        return $this->perform(
            fn () => $this->adminService->createUser($request->validated() + ['is_active' => $request->boolean('is_active', true)]),
            'Pengguna berhasil ditambahkan.'
        );
    }

    public function updateUser(UpdateUserRequest $request, User $user): JsonResponse
    {
        return $this->perform(
            fn () => $this->adminService->updateUser($user, $request->validated(), $request->user()),
            'Data pengguna berhasil diperbarui.'
        );
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        return $this->perform(
            fn () => $this->adminService->toggleStatus($user, $request->user()),
            'Status akun berhasil diubah.'
        );
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $password = $this->adminService->resetPassword($user, $request->user());

        return response()->json([
            'success'  => true,
            'message'  => 'Password berhasil direset. Sampaikan password sementara ini kepada pengguna.',
            'password' => $password,
        ]);
    }

    // ------------------------------------------------------------------ Role & Hak Akses

    public function roles(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.admin.roles',
            'roles.admin.partials.role-table-rows',
            $this->adminService->getUsers($request->only(self::FILTER_KEYS)),
            [
                'roleOptions' => User::ROLE_LABELS,
                'initialRole' => '',
                'permissions' => AdminService::PERMISSIONS,
                'counts'      => $this->adminService->getDashboardStats()['roles'],
            ]
        );
    }

    public function updateRole(UpdateRoleRequest $request, User $user): JsonResponse
    {
        return $this->perform(
            fn () => $this->adminService->changeRole($user, $request->validated('role'), $request->user()),
            'Role pengguna berhasil diperbarui.'
        );
    }

    // ------------------------------------------------------------------ Profil Admin

    public function profile(Request $request): View
    {
        return view('roles.admin.profile', ['user' => $request->user()]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $this->adminService->updateProfile($request->user(), $request->validated());

        return redirect()->route('admin.profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    // ------------------------------------------------------------------ helper

    private function userPayload(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'role_label'  => $user->role_label,
            'institution' => $user->institution,
            'phone'       => $user->phone,
            'is_active'   => $user->is_active,
        ];
    }
}
