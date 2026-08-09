<?php

namespace Database\Seeders;

use App\Models\AuditEvent;
use App\Models\KnowledgeItem;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenthline = Project::updateOrCreate(
            ['slug' => 'tenthline'],
            [
                'name' => 'Tenthline (LegalLine)',
                'status' => 'beta',
                'objective' => 'Prepare Tenthline for beta: reliable OCR processing of uploaded legal documents.',
                'description' => 'Legal case / support ticket management platform with PDF upload and OCR (PaddleOCR) processing.',
            ]
        );

        $tenthline->repositories()->updateOrCreate(
            ['name' => 'backend'],
            [
                'provider' => 'github',
                'repository' => 'mobipine/mp_tenthline_api',
                'role' => 'backend',
                'local_path' => '~/Sites/tenthline-api',
                'default_branch' => 'enhancements',
            ]
        );

        $tenthline->repositories()->updateOrCreate(
            ['name' => 'frontend'],
            [
                'provider' => 'github',
                'repository' => 'mobipine/mp_tenthline',
                'role' => 'frontend',
                'local_path' => '~/workprojects/tenthline',
                'default_branch' => 'main',
            ]
        );

        $tenthline->environments()->updateOrCreate(
            ['name' => 'local'],
            [
                'domain' => 'tenthline-api.test',
                'notes' => 'Served via Laravel Valet on the personal machine. Frontend run separately via `npm run dev` (Nuxt).',
            ]
        );

        $tenthline->knowledgeItems()->create([
            'type' => 'fact',
            'title' => 'Local database name',
            'body' => "Backend local MySQL database is named `legal_line` (DB credentials are NOT stored here — see local .env).",
        ]);

        $tenthline->knowledgeItems()->create([
            'type' => 'goal',
            'title' => 'Staging/production environments not yet documented',
            'body' => 'Only the local environment is recorded so far. Add staging/production domains, servers, and deployment method once confirmed by the owner — do not assume a deployment target.',
        ]);

        AuditEvent::record('owner', 'project.onboarded', $tenthline, 'Tenthline onboarded into muchiri_os as the pilot project.');

        $bancassurance = Project::updateOrCreate(
            ['slug' => 'bancassurance'],
            [
                'name' => 'Bancassurance (Co-op Bank)',
                'status' => 'active',
                'description' => 'Co-op Bank bancassurance customer portal. Roots Bedrock/Sage (Acorn) WordPress project.',
            ]
        );

        $bancassurance->repositories()->updateOrCreate(
            ['name' => 'theme'],
            [
                'provider' => 'github',
                'repository' => 'belvadigitalke/bancassurance',
                'role' => 'other',
                'local_path' => '~/Sites/client-onboarding/web/app/themes/bancassurance-theme',
                'default_branch' => 'customer-portal',
            ]
        );

        $bancassurance->knowledgeItems()->create([
            'type' => 'fact',
            'title' => 'Stack',
            'body' => 'Sage theme on Acorn ^4.3 (Laravel-flavored WordPress via roots/acorn), Livewire, Corcel. Bedrock root at ~/Sites/client-onboarding is not itself a git repo — only the theme directory is version controlled.',
        ]);

        $bancassurance->knowledgeItems()->create([
            'type' => 'fact',
            'title' => 'Fork relationship',
            'body' => 'origin is belvadigitalke/bancassurance; upstream is CO-OPBANK/coop_banckassurance.git. Changes may need to flow both ways — confirm the intended workflow before automating pushes.',
        ]);

        $bancassurance->knowledgeItems()->create([
            'type' => 'observation',
            'title' => 'Repo already has AI agent context files',
            'body' => 'The theme repo contains its own AGENTS.md and CLAUDE.md. Review those before running any autonomous agent against this repo, since they may encode project-specific conventions that should take precedence over generic ECP defaults.',
        ]);

        AuditEvent::record('owner', 'project.onboarded', $bancassurance, 'Bancassurance onboarded into muchiri_os.');
    }
}
