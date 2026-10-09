<?php

namespace App\Services;

use App\Models\Manuscript;
use Illuminate\Support\Str;

/**
 * CitationFormatter — format kutipan artikel (APA, IEEE, MLA) dari metadata naskah terbit.
 * Pemisahan nama sederhana: kata terakhir = nama keluarga (cukup untuk penamaan umum di Indonesia).
 * Teks dikembalikan polos; Blade yang meng-escape.
 */
class CitationFormatter
{
    /** @return array{apa: string, ieee: string, mla: string} */
    public function all(Manuscript $m): array
    {
        return [
            'apa'  => $this->apa($m),
            'ieee' => $this->ieee($m),
            'mla'  => $this->mla($m),
        ];
    }

    public function apa(Manuscript $m): string
    {
        $names = $this->names($m)->map(fn (array $n) => "{$n['family']}, {$n['initials']}");
        $authors = $this->join($names->all(), ', ', ', & ');

        return $this->tidy("{$authors} ({$this->year($m)}). {$m->title}. {$this->journal()}, {$this->volumeIssue($m)}." . $this->doi($m));
    }

    public function ieee(Manuscript $m): string
    {
        $names = $this->names($m)->map(fn (array $n) => "{$n['initials']} {$n['family']}");
        $authors = $this->join($names->all(), ', ', ', and ');
        $vol = $m->issue ? "vol. {$m->issue->volume}, no. {$m->issue->number}, " : '';

        return $this->tidy("{$authors}, \"{$m->title},\" {$this->journal()}, {$vol}{$this->year($m)}." . $this->doi($m));
    }

    public function mla(Manuscript $m): string
    {
        $names = $this->names($m)->values();
        $first = $names->first();
        $firstPart = $first ? "{$first['family']}, {$first['given']}" : '';
        $authors = match ($names->count()) {
            0       => '',
            1       => $firstPart,
            2       => "{$firstPart}, and {$names[1]['given']} {$names[1]['family']}",
            default => "{$firstPart}, et al",
        };
        $vol = $m->issue ? "vol. {$m->issue->volume}, no. {$m->issue->number}, " : '';

        return $this->tidy("{$authors}. \"{$m->title}.\" {$this->journal()}, {$vol}{$this->year($m)}." . $this->doi($m));
    }

    // -------------------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, array{family: string, given: string, initials: string}> */
    private function names(Manuscript $m)
    {
        return collect([$m->author?->name])
            ->merge(collect($m->co_authors ?? [])->pluck('name'))
            ->filter()
            ->map(function (string $full) {
                $parts = preg_split('/\s+/', trim($full));
                $family = array_pop($parts) ?: $full;
                $given = implode(' ', $parts);
                $initials = collect($parts)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)) . '.')->implode(' ');

                return ['family' => $family, 'given' => $given ?: $family, 'initials' => $initials];
            })
            ->values();
    }

    /** Gabungkan daftar dengan pemisah berbeda untuk elemen terakhir. */
    private function join(array $items, string $sep, string $lastSep): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode($sep, $items) . $lastSep . $last;
    }

    private function year(Manuscript $m): string
    {
        return ($m->published_at ?? $m->issue?->published_at)?->format('Y') ?? (string) ($m->issue?->year ?? 'n.d.');
    }

    private function volumeIssue(Manuscript $m): string
    {
        return $m->issue ? "{$m->issue->volume}({$m->issue->number})" : '';
    }

    private function journal(): string
    {
        return config('simpil.journal.name');
    }

    private function doi(Manuscript $m): string
    {
        return $m->doi ? " https://doi.org/{$m->doi}" : '';
    }

    private function tidy(string $text): string
    {
        return trim(preg_replace(['/\s+/', '/\s+([,.])/', '/\.\./'], [' ', '$1', '.'], $text));
    }
}
