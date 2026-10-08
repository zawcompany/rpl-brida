<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Manuscript;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function issues(): View
    {
        $issues = Issue::where('status', Issue::PUBLISHED)
            ->withCount([
                'manuscripts' => function ($query) {
                    $query->where('status', 'diterbitkan');
                }
            ])
            ->orderByDesc('year')
            ->orderByDesc('volume')
            ->orderByDesc('number')
            ->get();

        return view('public.issues.index', compact('issues'));
    }

    public function issue(Issue $issue): View
    {
        abort_unless($issue->isPublished(), 404);

        $manuscripts = $issue->manuscripts()
            ->with(['author', 'researchField'])
            ->where('status', 'diterbitkan')
            ->orderBy('id')
            ->get();

        return view('public.issues.show', compact('issue', 'manuscripts'));
    }

    public function article(Manuscript $manuscript): View
    {
        abort_unless(
            $manuscript->status === 'diterbitkan'
            && $manuscript->issue?->isPublished(),
            404
        );

        $manuscript->load(['author', 'researchField', 'issue']);

        return view('public.articles.show', compact('manuscript'));
    }
}