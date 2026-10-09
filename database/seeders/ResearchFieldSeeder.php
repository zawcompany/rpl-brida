<?php

namespace Database\Seeders;

use App\Models\ResearchField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Master data Bidang Keahlian / Rumpun Ilmu. Idempoten (upsert berdasarkan slug),
 * aman dijalankan berulang: php artisan db:seed --class=ResearchFieldSeeder
 *
 * Lima bidang pertama dipertahankan di urutan semula karena dirujuk DatabaseSeeder (data contoh).
 */
class ResearchFieldSeeder extends Seeder
{
    public const FIELDS = [
        // Rumpun komputasi
        'Ilmu Komputer',
        'Teknik Informatika',
        'Sistem Informasi',
        'Kecerdasan Buatan',
        'Keamanan Siber',
        'Data Science dan Analitik',
        'Jaringan dan Telekomunikasi',
        // Rumpun teknik & sains
        'Teknik Elektro',
        'Teknik Sipil',
        'Teknik Industri',
        'Teknik Lingkungan',
        'Arsitektur dan Perencanaan Wilayah',
        'Energi dan Sumber Daya Alam',
        'Transportasi dan Logistik',
        'Matematika dan Statistika',
        'Fisika dan Kimia',
        // Rumpun ekonomi & sosial
        'Manajemen',
        'Akuntansi dan Keuangan',
        'Ekonomi Pembangunan',
        'Administrasi dan Kebijakan Publik',
        'Inovasi dan Pembangunan Daerah',
        'Ilmu Sosial',
        'Ilmu Politik dan Pemerintahan',
        'Hukum',
        'Komunikasi dan Media',
        'Psikologi',
        'Pariwisata',
        'Budaya dan Humaniora',
        // Rumpun pendidikan, kesehatan, hayati
        'Pendidikan',
        'Kesehatan Masyarakat',
        'Kedokteran dan Keperawatan',
        'Pertanian',
        'Perikanan dan Kelautan',
        'Kehutanan dan Lingkungan Hidup',
    ];

    public function run(): void
    {
        foreach (self::FIELDS as $name) {
            ResearchField::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
