<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ComplaintService — sarana pengaduan dan keberatan (UU 25/2009 Pasal 36):
 * pengguna mengirim pengaduan, petugas (admin) menanggapi dan menuntaskannya dalam batas waktu.
 */
class ComplaintService
{
    public function __construct(private readonly NotificationService $notifier) {}

    /** @return Collection<int, Complaint> */
    public function listFor(User $user): Collection
    {
        return Complaint::where('user_id', $user->id)->latest()->get();
    }

    /**
     * @param  array{category: string, subject: string, description: string, manuscript_id?: int|null}  $data
     */
    public function submit(User $user, array $data): Complaint
    {
        $complaint = Complaint::create($data + ['user_id' => $user->id, 'status' => 'diterima']);

        ActivityLog::record('complaint.submitted', "Pengaduan {$complaint->ticket} dikirim oleh {$user->name}", $user->id);
        $this->notifier->complaintSubmitted($complaint);

        return $complaint;
    }

    /** Filter opsional: status. Pengaduan yang belum selesai tampil lebih dulu. */
    public function paginate(?string $status = null): LengthAwarePaginator
    {
        return Complaint::with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("case when status = 'selesai' then 1 else 0 end")
            ->oldest()
            ->paginate(15)
            ->withQueryString();
    }

    public function countOpen(): int
    {
        return Complaint::open()->count();
    }

    /**
     * @param  array{status: string, response?: string|null}  $data
     */
    public function respond(Complaint $complaint, User $officer, array $data): Complaint
    {
        $complaint->update([
            'status' => $data['status'],
            'response' => $data['response'] ?? $complaint->response,
            'handled_by' => $officer->id,
            'responded_at' => now(),
        ]);

        ActivityLog::record('complaint.updated', "Pengaduan {$complaint->ticket} diperbarui menjadi \"{$complaint->status_label}\"", $officer->id);
        $this->notifier->complaintUpdated($complaint);

        return $complaint;
    }
}
