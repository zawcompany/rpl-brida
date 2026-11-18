<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji asap: setiap rute GET dibuka sebagai tamu dan sebagai tiap peran, dengan id yang ada,
 * id yang tidak ada, dan id sampah. Tidak boleh ada yang berakhir 500 (galat server).
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $autoJoinDefaultJournal = false;

    public function test_no_get_route_returns_server_error_for_any_role_or_bad_input(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $valid = [
            'id'         => (string) (Manuscript::value('id') ?? 1),
            'manuscript' => (string) (Manuscript::value('id') ?? 1),
            'review'     => (string) (Review::value('id') ?? 1),
            'issue'      => (string) (Issue::value('id') ?? 1),
            'journal'    => Journal::value('slug'),
            'user'       => (string) User::value('id'),
            'field'      => (string) (ResearchField::value('id') ?? 1),
            'type'       => 'original',
            'role'       => 'Author',
            'hash'       => 'x',
        ];

        $actors = ['tamu' => null];
        foreach (['admin', 'author', 'editor', 'reviewer'] as $name) {
            $actors[$name] = User::where('email', "{$name}@example.com")->firstOrFail();
        }

        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = $route->uri();
            if (str_starts_with($uri, '_') || str_starts_with($uri, 'storage') || str_starts_with($uri, 'up')
                || str_starts_with($uri, 'sanctum') || str_contains($uri, 'dev-login')) {
                continue;
            }

            $variants = [];
            foreach (['valid' => null, 'tidak-ada' => '999999', 'sampah' => "abc'\"<x>"] as $label => $override) {
                $url = preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($valid, $override) {
                    return rawurlencode($override ?? ($valid[$m[1]] ?? '1'));
                }, $uri);
                $variants[$label] = '/' . ltrim($url, '/');
                if (! str_contains($uri, '{')) {
                    break;
                }
            }

            foreach ($actors as $actorName => $user) {
                foreach ($variants as $label => $url) {
                    foreach (['', '?page=-1&q=' . rawurlencode("'%<script>") . '&dari=dashboard', '?page[]=1&status[]=x&q[]=a', '?search[]=a&date[]=1&role[]=x&per_page[]=9&dari[]=dashboard', '?per_page=999999&date=bukan-tanggal&role=Hacker&status=zzz'] as $qs) {
                        auth()->logout();
                        if ($user) {
                            $this->actingAs($user);
                        }
                        $status = $this->get($url . $qs)->getStatusCode();
                        if ($status >= 500) {
                            $failures[] = "{$status} {$actorName} GET {$url}{$qs} ({$label})";
                        }
                    }
                }
            }
        }

        $this->assertSame([], $failures, "Rute yang galat:\n" . implode("\n", $failures));
    }
}
