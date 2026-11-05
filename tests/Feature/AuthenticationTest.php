<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase; // Mengosongkan/reset database testing setiap kali pengujian berjalan

    /** @test */
    public function user_dapat_mendaftar_akun_baru()
    {
        $response = $this->post('/register', [
            'name' => 'User Kutes',
            'email' => 'userkutes@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Memastikan sistem mengarahkan (redirect) setelah registrasi sukses
        $response->assertRedirect('/dashboard'); // Sesuaikan rute redirect aplikasi Anda
        
        // Memastikan data pengguna tersimpan di database
        $this->assertDatabaseHas('users', [
            'email' => 'userkutes@example.com',
        ]);
    }

    /** @test */
    public function user_dapat_login_dengan_kredensial_yang_benar()
    {
        // Buat user dummy
        $user = User::factory()->create([
            'email' => 'teslogin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'teslogin@example.com',
            'password' => 'password123',
        ]);

        // Memastikan user berhasil terautentikasi dan diarahkan ke dashboard
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard'); // Sesuaikan rute redirect aplikasi Anda
    }

    /** @test */
    public function user_gagal_login_dengan_password_salah()
    {
        $user = User::factory()->create([
            'email' => 'teslogin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'teslogin@example.com',
            'password' => 'passwordsalah',
        ]);

        // Memastikan user tidak terautentikasi
        $this->assertGuest();
    }

    public function test_halaman_login_tidak_boleh_di_cache_browser_dan_user_yang_masuk_dialihkan()
    {
        $this->assertStringContainsString('no-store', $this->get('/login')->assertOk()->headers->get('Cache-Control'));

        // Tombol Back -> muat ulang dari server -> middleware guest mengalihkan ke dashboard
        $user = User::factory()->create();
        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
    }
}
