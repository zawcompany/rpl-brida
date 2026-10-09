<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewerController extends Controller
{
    /**
     * Menampilkan daftar naskah yang ditugaskan
     * kepada reviewer yang sedang login.
     */
    public function index()
    {
        $reviews = Review::with(['manuscript.researchField'])
            ->where('reviewer_id', Auth::id())
            ->whereNull('superseded_at')
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->get();

        $manuscripts = $reviews->map(function ($review) {
            $status = match ($review->status) {
                'ditugaskan' => 'Belum Direview',
                'diterima' => 'Sedang Direview',
                'selesai' => 'Selesai Direview',
                'ditolak_reviewer' => 'Ditolak',
                default => 'Belum Direview',
            };

            $action = match ($review->status) {
                'ditugaskan' => 'Review',
                'diterima' => 'Lanjut',
                'selesai' => 'Selesai',
                'ditolak_reviewer' => 'Ditolak',
                default => 'Review',
            };

            return [
                'id' => $review->id,
                'no' => $review->id,
                'title' => $review->manuscript->title ?? 'Naskah tidak ditemukan',
                'field' => $review->manuscript->researchField->name ?? '-',
                'deadline' => $review->due_at
                    ? $review->due_at->format('d/m/Y')
                    : '-',
                'status' => $status,
                'action' => $action,
            ];
        });

        return view('roles.reviewer.manuscripts', compact('manuscripts'));
    }

    /**
     * Menampilkan detail penugasan review.
     */
    public function show($id)
    {
        $review = Review::with('manuscript')
            ->where('reviewer_id', Auth::id())
            ->whereNull('superseded_at')
            ->findOrFail($id);

        return view('roles.reviewer.review-detail', compact('review'));
    }

    /**
     * Menampilkan daftar naskah yang sudah selesai direview.
     */
    public function completed()
    {
        $reviews = Review::with(['manuscript.researchField'])
            ->where('reviewer_id', Auth::id())
            ->whereNull('superseded_at')
            ->where('status', 'selesai')
            ->orderByDesc('updated_at')
            ->get();

        return view(
            'roles.reviewer.manuscripts-selesai',
            compact('reviews')
        );
    }
}

