<?php

namespace Tests\Feature;

use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearchFieldAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_only_admin_can_manage_fields(): void
    {
        $this->actingAs($this->user('Editor'))->get(route('admin.fields.index'))->assertForbidden();
        $this->post(route('admin.fields.store'), ['name' => 'X'])->assertForbidden();
    }

    public function test_default_fields_are_available_without_running_seeder(): void
    {
        $this->assertSame(0, ResearchField::count());

        $this->actingAs($this->user('Reviewer'))->get(route('profile.show'))
            ->assertOk()->assertSee('Rekayasa Perangkat Lunak')->assertSee('name="research_field_ids[]"', false);

        $this->assertGreaterThan(30, ResearchField::count());
    }

    public function test_admin_can_add_and_rename_field_with_unique_names(): void
    {
        $admin = $this->user('Administrator');
        ResearchField::create(['name' => 'Bidang Lama', 'slug' => 'bidang-lama']); // tabel tidak kosong -> tanpa seed bawaan

        $this->actingAs($admin)->post(route('admin.fields.store'), ['name' => '  Quantum   Computing '])
            ->assertRedirect(route('admin.fields.index'))->assertSessionHas('success');
        $field = ResearchField::where('slug', 'quantum-computing')->firstOrFail();
        $this->assertSame('Quantum Computing', $field->name);

        $this->post(route('admin.fields.store'), ['name' => 'quantum computing'])->assertSessionHasErrors('name');

        $this->put(route('admin.fields.update', $field), ['name' => 'Komputasi Kuantum'])->assertSessionHasNoErrors();
        $this->assertSame('Komputasi Kuantum', $field->fresh()->name);

        // nama sendiri boleh disimpan ulang; nama bidang lain ditolak
        $this->put(route('admin.fields.update', $field), ['name' => 'Komputasi Kuantum'])->assertSessionHasNoErrors();
        $this->put(route('admin.fields.update', $field), ['name' => 'Bidang Lama'])->assertSessionHasErrors('name');
        $this->get(route('admin.fields.index'))->assertOk()->assertSee('Komputasi Kuantum');
    }
}
