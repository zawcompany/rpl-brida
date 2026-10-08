<?php

namespace Database\Seeders;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed 4 akun default multi-role + data dummy naskah & bidang penelitian.
     * Password tunggal: password123 (satu hash, DRY).
     */
    public function run(): void
    {
        $password = Hash::make('password123');

        // ------------------------------------------------------------------
        // 1. Users (4 role)
        // ------------------------------------------------------------------
        $users = [
            ['name' => 'Admin SIMPIL',    'email' => 'admin@example.com',    'role' => 'Administrator'],
            ['name' => 'Author SIMPIL',   'email' => 'author@example.com',   'role' => 'Author'],
            ['name' => 'Editor SIMPIL',   'email' => 'editor@example.com',   'role' => 'Editor'],
            ['name' => 'Reviewer SIMPIL', 'email' => 'reviewer@example.com', 'role' => 'Reviewer'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => $password])
            );
        }

        // Tambahan reviewer
        $reviewer2 = User::updateOrCreate(
            ['email' => 'reviewer2@example.com'],
            ['name' => 'Reviewer Dua', 'role' => 'Reviewer', 'password' => $password]
        );
        $reviewer3 = User::updateOrCreate(
            ['email' => 'reviewer3@example.com'],
            ['name' => 'Reviewer Tiga', 'role' => 'Reviewer', 'password' => $password]
        );

        // ------------------------------------------------------------------
        // 2. Bidang Penelitian
        // ------------------------------------------------------------------
        $fields = [
            ['name' => 'Ilmu Komputer',         'slug' => 'ilmu-komputer'],
            ['name' => 'Teknik Informatika',     'slug' => 'teknik-informatika'],
            ['name' => 'Sistem Informasi',       'slug' => 'sistem-informasi'],
            ['name' => 'Kecerdasan Buatan',      'slug' => 'kecerdasan-buatan'],
            ['name' => 'Keamanan Siber',         'slug' => 'keamanan-siber'],
        ];

        $createdFields = [];
        foreach ($fields as $f) {
            $createdFields[] = ResearchField::updateOrCreate(['slug' => $f['slug']], $f);
        }

        // ------------------------------------------------------------------
        // 3. Reviewer Qualifications (pivot)
        // ------------------------------------------------------------------
        $reviewer1 = User::where('email', 'reviewer@example.com')->first();
        $reviewer1->researchFields()->syncWithoutDetaching([$createdFields[0]->id, $createdFields[3]->id]);
        $reviewer2->researchFields()->syncWithoutDetaching([$createdFields[1]->id, $createdFields[2]->id]);
        $reviewer3->researchFields()->syncWithoutDetaching([$createdFields[4]->id]);

        // ------------------------------------------------------------------
        // 4. Naskah dummy
        // ------------------------------------------------------------------
        $author = User::where('email', 'author@example.com')->first();

        $manuscripts = [
            ['title' => 'Implementasi Deep Learning untuk Deteksi Anomali Jaringan', 'status' => 'pending',            'field_idx' => 0],
            ['title' => 'Analisis Performa Algoritma Sorting pada Dataset Besar',    'status' => 'pemeriksaan_awal',   'field_idx' => 1],
            ['title' => 'Sistem Rekomendasi Kolaboratif Berbasis Faktorisasi Matriks','status' => 'ditinjau',           'field_idx' => 3],
            ['title' => 'Evaluasi Keamanan Protokol TLS 1.3 pada Infrastruktur Cloud','status' => 'menunggu_keputusan','field_idx' => 4],
            ['title' => 'Pengembangan Chatbot Cerdas dengan Transformer Architecture', 'status' => 'disetujui',        'field_idx' => 3],
            ['title' => 'Optimasi Query Database NoSQL untuk Aplikasi Real-time',     'status' => 'pending',           'field_idx' => 2],
        ];

        foreach ($manuscripts as $ms) {
            Manuscript::updateOrCreate(
                ['title' => $ms['title']],
                [
                    'author_id'          => $author->id,
                    'research_field_id'  => $createdFields[$ms['field_idx']]->id,
                    'abstract'           => 'Ini adalah abstrak singkat dari naskah ilmiah berjudul "' . $ms['title'] . '". Penelitian ini bertujuan untuk mengeksplorasi topik yang relevan dengan perkembangan teknologi terkini.',
                    'keywords'           => 'machine learning, deep learning, penelitian, teknologi, inovasi',
                    'status'             => $ms['status'],
                    'submitted_at'       => now()->subDays(rand(1, 30)),
                ]
            );
        }
    }
}
