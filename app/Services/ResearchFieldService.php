<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Database\Seeders\ResearchFieldSeeder;
use Illuminate\Support\Str;

/** Master data Bidang Keahlian: dipakai profil Reviewer, form naskah, dan filter artikel publik. */
class ResearchFieldService
{
    /**
     * Pastikan daftar bidang bawaan tersedia: bila tabel masih kosong (mis. seeder belum dijalankan), isi dari
     * ResearchFieldSeeder. Setelah ada isi, admin bebas menambah/mengubah; daftar tidak dikembalikan otomatis.
     */
    private function ensureDefaults(): void
    {
        if (! ResearchField::query()->exists()) {
            (new ResearchFieldSeeder())->run();
        }
    }

    /** @return \Illuminate\Support\Collection<int, ResearchField> id + nama, urut nama (untuk pilihan di form) */
    public function options(): \Illuminate\Support\Collection
    {
        $this->ensureDefaults();

        return ResearchField::orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, ResearchField> urut nama, beserta jumlah reviewer dan naskah */
    public function list(): Collection
    {
        $this->ensureDefaults();

        return ResearchField::query()->withCount(['reviewers', 'manuscripts'])->orderBy('name')->get();
    }

    public function create(string $name, User $actor): ResearchField
    {
        $field = ResearchField::create(['name' => $name, 'slug' => Str::slug($name)]);

        ActivityLog::record('field.created', "Bidang keahlian \"{$field->name}\" ditambahkan", $actor->id);

        return $field;
    }

    public function rename(ResearchField $field, string $name, User $actor): ResearchField
    {
        $old = $field->name;
        $field->update(['name' => $name, 'slug' => Str::slug($name)]);

        if ($old !== $field->name) {
            ActivityLog::record('field.updated', "Bidang keahlian \"{$old}\" diubah menjadi \"{$field->name}\"", $actor->id);
        }

        return $field;
    }
}
