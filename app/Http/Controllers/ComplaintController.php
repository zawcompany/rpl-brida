<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengaduan dan keberatan.
 *  - Semua pengguna: mengirim pengaduan dan memantau tanggapannya.
 *  - Administrator: petugas pengelola pengaduan (menanggapi, mengubah status).
 */
class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $complaints) {}

    // ------------------------------------------------------------------ Pengguna

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('complaints.index', [
            'complaints' => $this->complaints->listFor($user),
            'manuscripts' => $user->manuscripts()->latest('submitted_at')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Complaint::CATEGORIES))],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'manuscript_id' => ['nullable', 'integer', Rule::exists('manuscripts', 'id')->where('author_id', $user->id)],
        ], [
            'description.min' => 'Uraikan pengaduan minimal 20 karakter agar dapat ditindaklanjuti.',
            'manuscript_id.exists' => 'Naskah yang dipilih bukan milik Anda.',
        ]);

        $complaint = $this->complaints->submit($user, $data);

        return redirect()->route('complaints.show', $complaint)
            ->with('success', "Pengaduan diterima dengan nomor {$complaint->ticket}. Kami menindaklanjutinya paling lambat ".config('simpil.complaints.response_days').' hari.');
    }

    public function show(Request $request, Complaint $complaint): View
    {
        abort_unless($complaint->user_id === $request->user()->id, 404);

        return view('complaints.show', ['complaint' => $complaint->load('manuscript')]);
    }

    // ------------------------------------------------------------------ Administrator

    public function adminIndex(Request $request): View
    {
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, Complaint::STATUSES) ? $status : null;

        return view('roles.admin.complaints', [
            'complaints' => $this->complaints->paginate($status),
            'status' => $status,
            'openCount' => $this->complaints->countOpen(),
        ]);
    }

    public function adminShow(Complaint $complaint): View
    {
        return view('roles.admin.complaint-show', ['complaint' => $complaint->load(['user', 'manuscript', 'handler'])]);
    }

    public function adminUpdate(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Complaint::STATUSES))],
            'response' => ['required_if:status,selesai', 'nullable', 'string', 'max:5000'],
        ], ['response.required_if' => 'Tuliskan tanggapan sebelum menandai pengaduan selesai.']);

        $this->complaints->respond($complaint, $request->user(), $data);

        return back()->with('success', 'Pengaduan diperbarui dan pengadu telah diberi tahu.');
    }
}
