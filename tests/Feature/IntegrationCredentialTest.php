<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IntegrationCredentialTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrg(string $slug = 'work'): Organization
    {
        return Organization::create(['name' => ucfirst($slug), 'slug' => $slug, 'kind' => 'employer', 'priority_weight' => 1]);
    }

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $org = $this->makeOrg();

        $integration = Integration::create([
            'organization_id' => $org->id,
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

    public function test_for_organization_scopes_credentials_and_has_no_fallback(): void
    {
        $work = $this->makeOrg('work');
        $personal = $this->makeOrg('personal');

        Integration::create(['organization_id' => $work->id, 'provider' => 'todoist', 'credentials' => ['token' => 'work-token'], 'is_active' => true]);

        $this->assertSame('work-token', Integration::forOrganization($work, 'todoist')?->credential('token'));
        // Personal has no integration configured -- must NOT fall back to work's.
        $this->assertNull(Integration::forOrganization($personal, 'todoist'));
    }

    public function test_inactive_integration_is_not_returned(): void
    {
        $org = $this->makeOrg();
        Integration::create(['organization_id' => $org->id, 'provider' => 'github', 'credentials' => ['token' => 'x'], 'is_active' => false]);

        $this->assertNull(Integration::forOrganization($org, 'github'));
    }

    public function test_create_form_saves_token_into_encrypted_credentials(): void
    {
        $user = User::factory()->create();
        $org = $this->makeOrg();

        Livewire::actingAs($user)
            ->test(\App\Filament\Resources\Integrations\Pages\CreateIntegration::class)
            ->fillForm([
                'organization_id' => $org->id,
                'provider' => 'todoist',
                'label' => 'My Todoist',
                'token' => 'abc123',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $integration = Integration::where('provider', 'todoist')->firstOrFail();
        $this->assertSame($org->id, $integration->organization_id);
        $this->assertSame('abc123', $integration->credential('token'));
    }

    public function test_edit_form_leaving_token_blank_keeps_existing_secret(): void
    {
        $user = User::factory()->create();
        $org = $this->makeOrg();
        $integration = Integration::create([
            'organization_id' => $org->id,
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
