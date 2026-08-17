<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_machine_defaults_to_both_after_migration(): void
    {
        $this->assertSame('both', Setting::get('active_machine'));
    }

    public function test_set_updates_the_value_and_bypasses_the_stale_cache(): void
    {
        Setting::set('active_machine', 'vps');

        $this->assertSame('vps', Setting::get('active_machine'));
        $this->assertSame('vps', \DB::table('settings')->where('key', 'active_machine')->value('value'));
    }

    public function test_get_returns_default_for_an_unknown_key(): void
    {
        $this->assertNull(Setting::get('does_not_exist'));
        $this->assertSame('fallback', Setting::get('does_not_exist', 'fallback'));
    }

    public function test_api_endpoint_returns_current_value(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/settings/active_machine')
            ->assertStatus(200)
            ->assertJson(['key' => 'active_machine', 'value' => 'both']);

        Setting::set('active_machine', 'mac');

        $this->getJson('/api/settings/active_machine')
            ->assertStatus(200)
            ->assertJson(['key' => 'active_machine', 'value' => 'mac']);
    }

    public function test_api_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/settings/active_machine')->assertStatus(401);
    }

    public function test_settings_page_saves_the_active_machine_choice(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Filament\Pages\ManageSettings::class)
            ->fillForm(['active_machine' => 'vps'])
            ->call('save');

        $this->assertSame('vps', Setting::get('active_machine'));
    }
}
