<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil Saya — satu controller untuk semua role. Tidak menerima id pengguna:
 * selalu bekerja pada $request->user() (tidak ada celah IDOR).
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.show', [
            'user' => $user->load('researchFields'),
            'summary' => $this->profiles->summaryFor($user),
            'expertise' => $this->profiles->expertiseOptions($user),
            'activity' => $this->profiles->activityOf($user),
        ]);
    }

    // ------------------------------------------------------------------ Hak atas data pribadi

    /** Unduh salinan data pribadi (UU 27/2022 Pasal 7). */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($this->profiles->exportPersonalData($user), 200, [
            'Content-Disposition' => 'attachment; filename="data-pribadi-simpil-'.$user->id.'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Halaman persetujuan kebijakan versi terbaru. */
    public function consent(Request $request): View|RedirectResponse
    {
        if ($request->user()->consent_version === config('simpil.legal.version')) {
            return redirect()->route('dashboard');
        }

        return view('profile.consent', ['user' => $request->user()]);
    }

    public function acceptConsent(Request $request): RedirectResponse
    {
        $request->validate(['consent' => ['accepted']], ['consent.accepted' => 'Centang persetujuan untuk melanjutkan.']);

        $this->profiles->acceptPolicy($request->user());

        return redirect()->intended(route('dashboard'))->with('success', 'Terima kasih, persetujuan Anda telah dicatat.');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $this->profiles->update($request->user(), $request->validated());

        return redirect()->route('profile.show')->with(
            'success',
            $user->wasChanged('email')
                ? 'Profil diperbarui. Tautan verifikasi dikirim ke email baru Anda.'
                : 'Profil berhasil diperbarui.'
        );
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $this->profiles->changePassword($request->user(), $request->validated('password'));

        return redirect()->route('profile.show')->with('success', 'Password berhasil diubah.');
    }

    // ------------------------------------------------------------------ Verifikasi email

    public function resendVerification(Request $request): RedirectResponse
    {
        $sent = $this->profiles->resendVerification($request->user());

        return redirect()->route('profile.show')->with(
            'success',
            $sent ? 'Tautan verifikasi telah dikirim ke email Anda.' : 'Email Anda sudah terverifikasi.'
        );
    }

    /** Tautan bertanda tangan dari email; EmailVerificationRequest memeriksa id & hash milik user ini. */
    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('profile.show')->with('success', 'Email berhasil diverifikasi.');
    }
}
