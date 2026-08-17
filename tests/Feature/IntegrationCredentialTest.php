<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IntegrationCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $integration = Integration::create([
            'provider' => 'todoist',
            'label' => 'Test',
            'credentials' => ['token' => 'super-secret-token'],
            'is_active' => true,
        ]);

        // Read the raw DB column, bypassing the model's decrypting cast.
        $raw = \DB::table('integrations')->where('id', $integration->id)->value('credentials');

        $this->assertStringNotContainsString('super-secret-token', $raw);
        $this->assertSame('super-secret-token', $integration->fresh()->credential('token'));
    }

    public function test_for_provider_only_returns_active_integrations(): void
    {
        Integration::create(['provider' => 'todoist', 'credentials' => ['token' => 'active-token'], 'is_active' => true]);
        Integration::create(['provider' => 'github', 'credentials' => ['token' => 'inactive-token'], 'is_active' => false]);

        $this->assertSame('active-token', Integration::forProvider('todoist')?->credential('token'));
        $this->assertNull(Integration::forProvider('github'));
    }

    public function test_create_form_saves_token_into_encrypted_credentials(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\Integrations\Pages\CreateIntegration::class)
            ->fillForm([
                'provider' => 'todoist',
                'label' => 'My Todoist',
                'token' => 'abc123',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $integration = Integration::where('provider', 'todoist')->firstOrFail();
        $this->assertSame('abc123', $integration->credential('token'));
    }

    public function test_edit_form_leaving_token_blank_keeps_existing_secret(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'provider' => 'todoist',
            'credentials' => ['token' => 'original-token'],
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\Integrations\Pages\EditIntegration::class, ['record' => $integration->getRouteKey()])
            ->fillForm(['label' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $integration->refresh();
        $this->assertSame('Renamed', $integration->label);
        $this->assertSame('original-token', $integration->credential('token'));
    }
}
