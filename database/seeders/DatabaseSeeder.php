<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed 4 akun default multi-role.
     * Password tunggal: password123 (satu hash, DRY).
     */
    public function run(): void
    {
        $password = Hash::make('password123');

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
    }
}
