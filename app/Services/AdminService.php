<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Manuscript;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * AdminService — logika bisnis administrator: statistik dashboard, manajemen pengguna,
 * toggle status akun, reset password, perubahan role (RBAC), dan profil admin.
 * Pelanggaran aturan dilempar sebagai DomainException (controller -> respons 422).
 */
class AdminService
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** Matriks hak akses per role (ditampilkan di menu Role & Hak Akses). */
    public const PERMISSIONS = [
        'Author'        => ['Mengajukan naskah', 'Melacak status naskah', 'Mengunggah revisi'],
        'Reviewer'      => ['Menerima/menolak penugasan review', 'Menilai naskah', 'Memberi rekomendasi'],
        'Editor'        => ['Menugaskan reviewer', 'Keputusan editorial', 'Mengelola edisi & publikasi'],
        'Administrator' => ['Mengelola pengguna', 'Mengatur role & hak akses', 'Memantau audit log'],
    ];

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    /**
     * @return array{total_users: int, roles: array<string,int>, total_manuscripts: int, new_users: int}
     */
    public function getDashboardStats(): array
    {
        $byRole = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return [
            'total_users'       => (int) $byRole->sum(),
            'roles'             => collect(User::ROLE_LABELS)
                ->mapWithKeys(fn (string $label, string $role) => [$label => (int) ($byRole[$role] ?? 0)])
                ->all(),
            'total_manuscripts' => Manuscript::count(),
            'new_users'         => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /** 5 log aktivitas sistem terbaru. */
    public function getRecentLogs(int $limit = 5): Collection
    {
        return ActivityLog::with('actor')->latest('created_at')->latest('id')->limit($limit)->get();
    }

    // -------------------------------------------------------------------------
    // Daftar pengguna
    // -------------------------------------------------------------------------

    /** Filter: search (nama/email), role (nilai DB atau kosong = semua), per_page. */
    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? null;

        return User::query()
            ->when(filled($search), fn (Builder $q) => $q->where(
                fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(filled($role) && isset(User::ROLE_LABELS[$role]), fn (Builder $q) => $q->where('role', $role))
            ->latest('created_at')->latest('id')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    // -------------------------------------------------------------------------
    // CRUD pengguna
    // -------------------------------------------------------------------------

    /** Password di-hash otomatis (cast 'hashed' = Bcrypt). */
    public function createUser(array $data): User
    {
        return User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => $data['password'],
            'role'        => $data['role'],
            'institution' => $data['institution'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'is_active'   => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * Perbarui data, role, dan status. Password hanya diganti bila diisi.
     *
     * @throws DomainException bila melanggar aturan keamanan akun
     */
    public function updateUser(User $user, array $data, User $actor): User
    {
        $role = $data['role'] ?? $user->role;
        $active = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $user->is_active;

        $this->guardAdminChange($user, $actor, $role, $active);

        $user->fill([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'role'        => $role,
            'institution' => $data['institution'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'is_active'   => $active,
        ]);
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $changes = array_keys($user->getDirty());
        $user->save();

        if ($changes) {
            ActivityLog::record('user.updated', "Data pengguna {$user->name} diperbarui (" . implode(', ', $changes) . ')', $actor->id);
        }

        return $user;
    }

    public function toggleStatus(User $user, User $actor): User
    {
        $this->guardAdminChange($user, $actor, $user->role, ! $user->is_active);

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLog::record(
            'user.status',
            'Akun ' . $user->name . ($user->is_active ? ' diaktifkan kembali' : ' ditangguhkan (suspend)'),
            $actor->id
        );

        return $user;
    }

    /**
     * Ubah role (RBAC).
     *
     * @throws DomainException
     */
    public function changeRole(User $user, string $role, User $actor): User
    {
        if ($user->role === $role) {
            return $user;
        }

        $this->guardAdminChange($user, $actor, $role, $user->is_active);

        $from = $user->role_label;
        $user->update(['role' => $role]);

        ActivityLog::record('user.role', "Role {$user->name} diubah: {$from} -> {$user->role_label}", $actor->id);

        return $user;
    }

    /**
     * Reset password ke sandi sementara acak. Sandi hanya dikembalikan sekali (tidak dicatat di log).
     */
    public function resetPassword(User $user, User $actor): string
    {
        $temporary = Str::password(12, symbols: false);
        $user->update(['password' => $temporary]);

        ActivityLog::record('user.password_reset', "Password {$user->name} direset oleh administrator", $actor->id);

        return $temporary;
    }

    // -------------------------------------------------------------------------
    // Profil admin (UC-05)
    // -------------------------------------------------------------------------

    public function updateProfile(User $admin, array $data): User
    {
        $admin->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (filled($data['password'] ?? null)) {
            $admin->password = $data['password'];
        }

        $admin->save();
        ActivityLog::record('profile.updated', "Profil administrator {$admin->name} diperbarui", $admin->id);

        return $admin;
    }

    // -------------------------------------------------------------------------
    // Aturan keamanan
    // -------------------------------------------------------------------------

    /**
     * Cegah admin mengunci diri sendiri atau menghilangkan admin aktif terakhir.
     *
     * @throws DomainException
     */
    private function guardAdminChange(User $target, User $actor, string $newRole, bool $newActive): void
    {
        $loseAdmin = $target->isAdmin() && $target->is_active && ($newRole !== 'Administrator' || ! $newActive);

        if (! $loseAdmin) {
            return;
        }

        if ($target->is($actor)) {
            throw new DomainException('Anda tidak dapat menurunkan role atau menangguhkan akun Anda sendiri.');
        }

        $otherAdmins = User::where('role', 'Administrator')->where('is_active', true)->whereKeyNot($target->id)->count();
        if ($otherAdmins === 0) {
            throw new DomainException('Minimal harus ada satu administrator aktif.');
        }
    }
}
