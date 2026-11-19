<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Manuscript;
use DOMDocument;
use DOMElement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Ekspor metadata artikel terbit per jurnal dalam Dublin Core (XML) atau JSON, untuk diserahkan/dipanen
 * oleh repositori nasional (RIN/BRIN) dan mesin pemanen metadata. Hanya artikel yang sudah terbit;
 * tidak ada data akun (surel, telepon) atau naskah belum terbit di dalamnya.
 *
 * Catatan: ini ekspor berkas (snapshot), bukan endpoint protokol OAI-PMH penuh.
 */
class MetadataExportController extends Controller
{
    private const NS_OAI_DC = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
    private const NS_DC     = 'http://purl.org/dc/elements/1.1/';

    public function journal(Journal $journal, string $format): Response|JsonResponse
    {
        abort_unless($journal->is_active, 404);

        $records = Manuscript::published()
            ->where('journal_id', $journal->id)
            ->with(['author', 'issue'])
            ->orderBy('id')
            ->get()
            ->map(fn (Manuscript $m) => $this->record($journal, $m));

        if ($format === 'json') {
            return response()->json([
                'journal' => [
                    'name'      => $journal->name,
                    'e_issn'    => $journal->e_issn,
                    'publisher' => $this->publisher(),
                    'license'   => Journal::licenseInfo($journal->license)['url'],
                    'url'       => route('journals.show', $journal),
                ],
                'generated_at' => now()->toIso8601String(),
                'count'        => $records->count(),
                'records'      => $records->values(),
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return response($this->xml($journal, $records->all()), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Satu rekaman metadata (nama kunci = elemen Dublin Core). */
    private function record(Journal $journal, Manuscript $m): array
    {
        $date    = $m->published_at ?? $m->issue?->published_at;
        $license = Journal::licenseInfo($m->license ?? $journal->license);

        $keywords = collect(preg_split('/[,;]+/', (string) $m->keywords))->map(fn ($k) => trim($k))->filter()->values()->all();

        $source = $journal->name . ($journal->e_issn ? " (e-ISSN {$journal->e_issn})" : '')
            . ($m->issue ? ', ' . $m->issue->label : '');

        return array_filter([
            'title'       => $m->title,
            'creator'     => collect([$m->author?->name])->merge(collect($m->co_authors ?? [])->pluck('name'))->filter()->values()->all(),
            'subject'     => $keywords,
            'description' => $m->abstract,
            'publisher'   => $this->publisher(),
            'date'        => $date?->format('Y-m-d'),
            'type'        => 'Text',
            'format'      => 'application/pdf',
            'identifier'  => array_values(array_filter([route('reader.article', $m->id), $m->doi ? 'https://doi.org/' . $m->doi : null])),
            'source'      => $source,
            'language'    => 'id',
            'rights'      => $license['url'],
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    private function publisher(): string
    {
        return 'Badan Riset dan Inovasi Daerah (BRIDA) Kota Makassar';
    }

    /** @param array<int, array<string, mixed>> $records */
    private function xml(Journal $journal, array $records): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('records');
        $root->setAttribute('journal', $journal->name);
        if ($journal->e_issn) {
            $root->setAttribute('e-issn', $journal->e_issn);
        }
        $root->setAttribute('generated', now()->toIso8601String());
        $doc->appendChild($root);

        foreach ($records as $rec) {
            $dc = $doc->createElementNS(self::NS_OAI_DC, 'oai_dc:dc');
            $dc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', self::NS_DC);

            foreach ($rec as $name => $values) {
                foreach ((array) $values as $value) {
                    /** @var DOMElement $el */
                    $el = $doc->createElementNS(self::NS_DC, "dc:{$name}");
                    $el->appendChild($doc->createTextNode((string) $value));
                    $dc->appendChild($el);
                }
            }

            $record = $doc->createElement('record');
            $record->appendChild($dc);
            $root->appendChild($record);
        }

        return $doc->saveXML();
    }
}
