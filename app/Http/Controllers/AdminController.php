<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsForEditor;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller tipis: validasi (FormRequest) -> AdminService -> respons.
 * Akses dijaga middleware auth + role:admin pada grup rute.
 */
class AdminController extends Controller
{
    use RespondsForEditor; // tableResponse() & perform() (DomainException -> 422)

    private const FILTER_KEYS = ['search', 'role', 'per_page'];

    public function __construct(private readonly AdminService $adminService) {}

    // ------------------------------------------------------------------ Dashboard

    public function dashboard(): View
    {
        return view('roles.admin.dashboard', [
            'stats' => $this->adminService->getDashboardStats(),
            'logs' => $this->adminService->getRecentLogs(),
        ]);
    }

    // ------------------------------------------------------------------ Jejak audit & pemberitahuan

    /** Kelompok aksi yang dapat difilter di halaman log aktivitas. */
    public const LOG_GROUPS = [
        'auth' => 'Masuk / keluar',
        'manuscript' => 'Naskah',
        'review' => 'Review',
        'issue' => 'Penerbitan',
        'file' => 'Akses berkas',
        'user' => 'Pengguna',
        'profile' => 'Profil',
        'journal' => 'Jurnal',
        'complaint' => 'Pengaduan',
        'privacy' => 'Data pribadi',
        'announcement' => 'Pemberitahuan',
    ];

    public function logs(Request $request): View
    {
        $search = $request->query('search');
        $action = $request->query('action');
        $filters = [
            'search' => is_string($search) ? $search : null,
            'action' => is_string($action) && array_key_exists($action, self::LOG_GROUPS) ? $action : null,
        ];

        return view('roles.admin.logs', [
            'logs' => $this->adminService->paginateLogs($filters),
            'filters' => $filters,
            'groups' => self::LOG_GROUPS,
        ]);
    }

    public function announcement(): View
    {
        return view('roles.admin.announcement', ['audiences' => ['all' => 'Semua pengguna aktif'] + User::ROLE_LABELS]);
    }

    public function sendAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', Rule::in(array_merge(['all'], array_keys(User::ROLE_LABELS)))],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $count = $this->adminService->announce($data['audience'], $data['title'], $data['message']);

        return back()->with('success', "Pemberitahuan dikirim ke {$count} pengguna.");
    }

    // ------------------------------------------------------------------ Kelola Pengguna

    public function users(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.admin.users',
            'roles.admin.partials.user-table-rows',
            $this->adminService->getUsers($this->filters($request, self::FILTER_KEYS)),
            ['roleOptions' => User::ROLE_LABELS, 'initialRole' => (string) ($this->filters($request, ['role'])['role'] ?? '')]
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
            'success' => true,
            'message' => 'Password berhasil direset. Sampaikan password sementara ini kepada pengguna.',
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
            $this->adminService->getUsers($this->filters($request, self::FILTER_KEYS)),
            [
                'roleOptions' => User::ROLE_LABELS,
                'initialRole' => '',
                'permissions' => AdminService::PERMISSIONS,
                'counts' => $this->adminService->getDashboardStats()['roles'],
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

    // ------------------------------------------------------------------ helper

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'institution' => $user->institution,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
        ];
    }
}
