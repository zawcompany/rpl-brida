<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase; // Mengosongkan/reset database testing setiap kali pengujian berjalan

    #[Test]
    public function user_dapat_mendaftar_akun_baru()
    {
        $response = $this->post('/register', [
            'name' => 'User Kutes',
            'email' => 'userkutes@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent' => '1',
        ]);

        // Memastikan sistem mengarahkan (redirect) setelah registrasi sukses
        $response->assertRedirect('/dashboard'); // Sesuaikan rute redirect aplikasi Anda

        // Memastikan data pengguna tersimpan di database
        $this->assertDatabaseHas('users', [
            'email' => 'userkutes@example.com',
        ]);
    }

    #[Test]
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

    #[Test]
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

    public function test_halaman_publik_tidak_di_cache_agar_tombol_back_menampilkan_status_login_terbaru(): void
    {
        $this->assertStringContainsString('no-store', $this->get('/')->assertOk()->headers->get('Cache-Control'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertOk()->assertSee('Dashboard')->assertDontSee(route('register'));
    }

    public function test_user_yang_sudah_masuk_membuka_daftar_diarahkan_ke_dashboard_dengan_penjelasan(): void
    {
        $user = User::factory()->create(['name' => 'Penulis Uji']);

        $this->actingAs($user)->get('/register')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth_notice', fn (string $notice) => str_contains($notice, 'Penulis Uji'));
    }

    public function test_ingat_saya_menyimpan_cookie_remember_dan_tanpa_centang_tidak(): void
    {
        $this->assertTrue(config('session.expire_on_close'));
        $user = User::factory()->create(['email' => 'ingat@example.com', 'password' => bcrypt('password123')]);
        $remember = auth()->guard('web')->getRecallerName();

        $this->post('/login', ['email' => 'ingat@example.com', 'password' => 'password123'])->assertCookieMissing($remember);
        $this->post('/logout');

        $this->post('/login', ['email' => 'ingat@example.com', 'password' => 'password123', 'remember' => '1'])
            ->assertCookie($remember);
        $this->assertNotNull($user->fresh()->remember_token);
    }
}
