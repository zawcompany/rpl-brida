<?php

namespace App\Http\Middleware;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Editor hanya boleh menyentuh naskah, edisi, dan reviewer milik jurnal tempat ia menjadi editor.
 * Dipasang pada rute editor; Administrator tidak dibatasi. Di luar jurnalnya jawabannya 404,
 * supaya keberadaan data jurnal lain tidak terbuka.
 */
class EnsureJournalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $request->user()?->editableJournalIds();

        if ($allowed === null) {
            return $next($request); // Administrator
        }

        $manuscript = $this->resolve($request->route('manuscript'), Manuscript::class);
        $issue      = $this->resolve($request->route('issue'), Issue::class);
        $reviewer   = $this->resolve($request->route('user'), User::class);

        if ($manuscript) {
            abort_unless(in_array($manuscript->journal_id, $allowed, true), 404);
        }

        if ($issue) {
            abort_unless(in_array($issue->journal_id, $allowed, true), 404);
        }

        if ($reviewer) {
            $shared = $reviewer->journals()
                ->where('journal_user.role', Journal::REVIEWER)
                ->whereIn('journals.id', $allowed)
                ->exists();

            abort_unless($shared, 404);
        }

        return $next($request);
    }

    /** Parameter rute bisa sudah berupa model (setelah binding) atau masih id mentah. */
    private function resolve(mixed $value, string $class): mixed
    {
        if ($value === null || $value instanceof $class) {
            return $value;
        }

        return $class::find($value);
    }
}
