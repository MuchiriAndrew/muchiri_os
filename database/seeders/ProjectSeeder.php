<?php

namespace Database\Seeders;

use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $belva = Organization::where('slug', 'belva-digital')->firstOrFail();
        $sideJobs = Organization::where('slug', 'side-jobs')->firstOrFail();
        $personal = Organization::where('slug', 'personal')->firstOrFail();

        // ── Belva Digital ─────────────────────────────────────────────

        $this->seedProject($belva, 'axis', [
            'name' => 'Axis',
            'status' => 'active',
            'objective' => "Grow and maintain the Axis customer engagement platform for Belva Digital's client.",
            'description' => 'Multi-product platform: Axis Engage (omnichannel messaging), Axis Admin (superadmin dashboard), Axis Workspace (AI chat + knowledge base), Axis Auth (SSO), an embeddable chat widget, and a WordPress marketing site at myaxis.ai.',
        ], repos: [
            ['name' => 'api', 'provider' => 'github', 'repository' => 'AXIS-LLC/axis-api', 'role' => 'backend', 'local_path' => '~/Sites/axis-api', 'default_branch' => 'main'],
            ['name' => 'engage-frontend', 'provider' => 'github', 'repository' => 'AXIS-LLC/axis-client', 'role' => 'frontend', 'local_path' => '~/workprojects/AXIS-ENGAGE/axis-engage-nuxt4', 'default_branch' => 'main'],
            ['name' => 'admin-dashboard', 'provider' => 'github', 'repository' => 'AXIS-LLC/axis-admin', 'role' => 'frontend', 'local_path' => '~/workprojects/nuxt-axis-admin', 'default_branch' => 'uat'],
            ['name' => 'workspace', 'provider' => 'github', 'repository' => 'AXIS-LLC/axis-gpt', 'role' => 'frontend', 'local_path' => '~/workprojects/axis-workspace', 'default_branch' => 'main'],
            ['name' => 'chat-widget', 'provider' => 'github', 'repository' => 'AXIS-LLC/axis-chat-widget', 'role' => 'frontend', 'local_path' => '~/workprojects/vue-widget-v3', 'default_branch' => 'main'],
            ['name' => 'auth', 'provider' => 'github', 'repository' => 'AXIS-LLC/axis-auth', 'role' => 'infrastructure', 'local_path' => '~/workprojects/axis-auth', 'default_branch' => 'main'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'Live admin dashboard is nuxt-axis-admin, not axis-admin', 'body' => "~/workprojects/axis-admin (branch main) is a stalled/parallel Vue+Vite rewrite of the AXIS-LLC/axis-admin repo — only 9 commits, last remote activity 2026-06-19. The LIVE dashboard is ~/workprojects/nuxt-axis-admin (branch uat), with commits up to 2026-08-09. Both directories check out the same GitHub repo on different branches — don't confuse them."],
            ['type' => 'observation', 'title' => 'Axis Auth may be dormant', 'body' => "axis-auth (SvelteKit SSO service, \"Sign In With Axis\") has had no commits since 2026-02-28 — over 5 months stale versus everything else in the family being touched within the last two weeks. Confirm with Andrew whether it's still part of the live stack before assuming it's active."],
            ['type' => 'fact', 'title' => 'myaxis.ai marketing site has no version control', 'body' => '~/Sites/myaxis is a plain WordPress install (theme based on twentytwentyfive) with no git repository at all. The myaxis.ai domain also serves as the CDN host for the embeddable chat widget (plugin.myaxis.ai).'],
        ]);

        $bancassurance = Project::updateOrCreate(
            ['slug' => 'bancassurance'],
            ['organization_id' => $belva->id, 'name' => 'Bancassurance (Co-op Bank)', 'status' => 'active',
                'description' => 'Co-op Bank bancassurance customer portal. Roots Bedrock/Sage (Acorn) WordPress project.'],
        );
        $bancassurance->repositories()->updateOrCreate(
            ['name' => 'theme'],
            ['provider' => 'github', 'repository' => 'belvadigitalke/bancassurance', 'role' => 'other', 'local_path' => '~/Sites/client-onboarding/web/app/themes/bancassurance-theme', 'default_branch' => 'customer-portal'],
        );
        $bancassurance->knowledgeItems()->updateOrCreate(['title' => 'Stack'], ['type' => 'fact',
            'body' => 'Sage theme on Acorn ^4.3 (Laravel-flavored WordPress via roots/acorn), Livewire, Corcel. Bedrock root at ~/Sites/client-onboarding is not itself a git repo — only the theme directory is version controlled.']);
        $bancassurance->knowledgeItems()->updateOrCreate(['title' => 'Fork relationship'], ['type' => 'fact',
            'body' => 'origin is belvadigitalke/bancassurance; upstream is CO-OPBANK/coop_banckassurance.git. Changes may need to flow both ways — confirm the intended workflow before automating pushes.']);
        $bancassurance->knowledgeItems()->updateOrCreate(['title' => 'Repo already has AI agent context files'], ['type' => 'observation',
            'body' => 'The theme repo contains its own AGENTS.md and CLAUDE.md. Review those before running any autonomous agent against this repo — they may encode project-specific conventions that take precedence over generic ECP defaults.']);
        $this->auditIfNew($bancassurance);

        // ── Side Jobs ─────────────────────────────────────────────────

        $tenthline = Project::updateOrCreate(
            ['slug' => 'tenthline'],
            ['organization_id' => $sideJobs->id, 'name' => 'Tenthline (LegalLine)', 'status' => 'beta',
                'objective' => 'Prepare Tenthline for beta: reliable OCR processing of uploaded legal documents.',
                'description' => 'Legal case / support ticket management platform with PDF upload and OCR (PaddleOCR) processing.'],
        );
        $tenthline->repositories()->updateOrCreate(['name' => 'backend'], ['provider' => 'github', 'repository' => 'mobipine/mp_tenthline_api', 'role' => 'backend', 'local_path' => '~/Sites/tenthline-api', 'default_branch' => 'enhancements']);
        $tenthline->repositories()->updateOrCreate(['name' => 'frontend'], ['provider' => 'github', 'repository' => 'mobipine/mp_tenthline', 'role' => 'frontend', 'local_path' => '~/workprojects/tenthline', 'default_branch' => 'main']);
        $tenthline->environments()->updateOrCreate(['name' => 'local'], ['domain' => 'tenthline-api.test', 'notes' => 'Served via Laravel Valet. Frontend run separately via `npm run dev` (Nuxt).']);
        $tenthline->knowledgeItems()->updateOrCreate(['title' => 'Local database name'], ['type' => 'fact', 'body' => 'Backend local MySQL database is named `legal_line` (credentials NOT stored here — see local .env).']);
        $tenthline->knowledgeItems()->updateOrCreate(['title' => 'Staging/production environments not yet documented'], ['type' => 'goal', 'body' => 'Only the local environment is recorded so far. Add staging/production once confirmed — do not assume a deployment target.']);
        $this->auditIfNew($tenthline);

        $this->seedProject($sideJobs, 'tibadesk', [
            'name' => 'TibaDesk', 'status' => 'active',
            'objective' => 'Grow TibaDesk as a healthcare/clinic management SaaS product.',
            'description' => 'Healthcare SaaS marketing site & platform (patient management, appointments, pharmacy, billing, insurance, radiology) for hospitals/clinics/diagnostics, WordPress + Elementor.',
        ], repos: [
            ['name' => 'wordpress-site', 'provider' => 'other', 'repository' => 'not version-controlled', 'role' => 'other', 'local_path' => '~/Sites/afyacentral', 'default_branch' => 'n/a'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'No git, but has substantive docs', 'body' => 'Not under version control. Custom theme "lexend" at wp-content/themes/lexend. Actively maintained internal docs: IMPLEMENTATION_PLAN.md, REUSABLE_SYSTEM_INVENTORY.md, design.md (last edits July 2026). Live at tibadesk.com.'],
            ['type' => 'observation', 'title' => 'Orphaned Jadility prototype inside this directory', 'body' => 'afyacentral/jadility/ contains static HTML mockups (index.html, pricing.html, broadcasting.html) — an early Jadility marketing-site prototype misplaced here. Worth relocating or cleaning up.'],
        ]);

        $this->seedProject($sideJobs, 'jadility', [
            'name' => 'Jadility', 'status' => 'active',
            'objective' => 'Grow Jadility as a SaaS marketing/broadcasting product.',
            'description' => 'SaaS product marketing site (messaging/broadcasting features), WordPress + Elementor with a purchased "saasland" theme.',
        ], repos: [
            ['name' => 'wordpress-site', 'provider' => 'other', 'repository' => 'not version-controlled', 'role' => 'other', 'local_path' => '~/Sites/jadility', 'default_branch' => 'n/a'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'No git; theme setup', 'body' => 'Not under version control. Custom "lexend" theme plus purchased "saasland" theme, ACF Pro, Redux Framework, WPForms. Actively edited (as recently as 2026-08-06) via the same MCP/WP-CLI Elementor workflow documented in ~/Sites/BUILD-PROCESS.md.'],
        ]);

        $this->seedProject($sideJobs, 'bizconnect-africa', [
            'name' => 'BizConnect Africa', 'status' => 'active',
            'objective' => 'Ship BizConnect Africa, a business marketing / lead-gen / unified-inbox SaaS.',
            'description' => 'Team project (Josphat, Ricky, Andrew) — unified inbox and lead-gen SaaS for African businesses.',
        ], repos: [
            ['name' => 'api', 'provider' => 'github', 'repository' => 'BizConnectOrg/bizconnect-api', 'role' => 'backend', 'local_path' => '~/Sites/bizconnect-api', 'default_branch' => 'main'],
            ['name' => 'client', 'provider' => 'github', 'repository' => 'BizConnectOrg/bizconnect-client', 'role' => 'frontend', 'local_path' => '~/myprojects/bizconnect-client', 'default_branch' => 'main'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'Stack and team', 'body' => "Laravel backend uses Africa's Talking SMS + Pusher; Nuxt 3 frontend. Team project per PRESENTATION.md in the client repo."],
        ]);

        $this->seedProject($sideJobs, 'tumizi', [
            'name' => 'Tumizi', 'status' => 'active',
            'objective' => 'Maintain the Tumizi platform and its mobile companion app for client Tumizi Hub Limited.',
            'description' => 'Platform + mobile app for client Tumizi Hub Limited.',
        ], repos: [
            ['name' => 'platform', 'provider' => 'github', 'repository' => 'Tumizi-Hub-Limited/tumizi-platform', 'role' => 'other', 'local_path' => '~/Sites/tumizi', 'default_branch' => 'main'],
            ['name' => 'mobile-app', 'provider' => 'github', 'repository' => 'Tumizi-Hub-Limited/tumizi-mobile-app', 'role' => 'other', 'local_path' => '~/myprojects/tumizi-mobile-app', 'default_branch' => 'main'],
        ], knowledge: [
            ['type' => 'goal', 'title' => 'Platform repo not yet deeply investigated', 'body' => "Only confirmed to exist and share the Tumizi-Hub-Limited GitHub org with the mobile app — stack/purpose not yet catalogued in detail. Mobile app is Expo/React Native, appears to be a payments/paybill-adjacent app per its AGENTS.md."],
        ]);

        // ── Personal ──────────────────────────────────────────────────

        $this->seedProject($personal, 'portfolio', [
            'name' => 'Portfolio', 'status' => 'active',
            'objective' => "Keep Andrew's personal portfolio site current.",
            'description' => 'Personal portfolio site with a CMS admin for projects/skills.',
        ], repos: [
            ['name' => 'site', 'provider' => 'github', 'repository' => 'MuchiriAndrew/Portfolio', 'role' => 'other', 'local_path' => '~/Sites/portfolio-v2', 'default_branch' => 'v2'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'Rewritten stack; old checkout excluded', 'body' => 'Current stack: Laravel + Inertia + Vue 3 + Filament admin, Tailwind. ~/myprojects/Portfolio is the SAME GitHub repo checked out on the old `main` branch (plain Vite+Vue+Bootstrap, last touched Jan 2025) — that directory is a stale superseded duplicate, not a separate project. Excluded from the catalogue.'],
        ]);

        $this->seedProject($personal, 'supportshelf-ai', [
            'name' => 'SupportShelf AI', 'status' => 'active',
            'objective' => 'Portfolio-grade demonstration of production RAG + realtime chat.',
            'description' => 'AI e-commerce customer-support assistant — RAG chat over crawled support articles/manuals/policies.',
        ], repos: [
            ['name' => 'app', 'provider' => 'github', 'repository' => 'MuchiriAndrew/support-shelf', 'role' => 'other', 'local_path' => '~/Sites/responses-api', 'default_branch' => 'main'],
        ], environments: [
            ['name' => 'production', 'domain' => 'supportshelf.mkbuilds.live'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'Folder name mismatch', 'body' => 'Local folder is named responses-api but the GitHub repo/product name is "support-shelf" / SupportShelf AI. Stack: Laravel 13, Filament 5, openai-php/laravel, Reverb/Pusher for realtime chat, smalot/pdfparser for ingestion. Extensive docs at docs/developer/ (01-09) and docs/supportshelf-project-writeup.md.'],
        ]);

        $this->seedProject($personal, 'personal-finance', [
            'name' => 'Personal Finance', 'status' => 'active',
            'objective' => "Build out Andrew's personal finance tracker.",
            'description' => 'Full-stack personal finance tracker monorepo.',
        ], repos: [
            ['name' => 'app', 'provider' => 'github', 'repository' => 'MuchiriAndrew/personal-finance', 'role' => 'other', 'local_path' => '~/myprojects/personal-finance', 'default_branch' => 'main'],
        ], knowledge: [
            ['type' => 'fact', 'title' => 'Monorepo structure', 'body' => 'personalfinance-api (Spring Boot 4 / Java, JPA, MySQL) + personalfinance-client (Next.js 16 / React 19), in one repo. Active — last commit Apr 2026 implementing JWT auth and user management.'],
        ]);

        $this->seedProject($personal, 'macrozone', [
            'name' => 'Macrozone', 'status' => 'beta',
            'objective' => 'Validate a meal/macro-tracking mobile app concept.',
            'description' => 'Early-stage meal/macro-tracking mobile app.',
        ], repos: [
            ['name' => 'app', 'provider' => 'other', 'repository' => 'local-only (no remote yet)', 'role' => 'other', 'local_path' => '~/myprojects/macrozone', 'default_branch' => 'main'],
        ], knowledge: [
            ['type' => 'goal', 'title' => 'Early stage', 'body' => 'Only 1 commit so far (May 2026), no GitHub remote configured yet. Expo/React Native, has real domain logic beyond the starter template (src/storage/meals.ts, RecentMeals.tsx, notifications).'],
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $repos
     * @param  array<int, array<string, string>>  $environments
     * @param  array<int, array<string, string>>  $decisions
     * @param  array<int, array<string, string>>  $knowledge
     */
    private function seedProject(Organization $org, string $slug, array $attrs, array $repos = [], array $environments = [], array $decisions = [], array $knowledge = []): Project
    {
        $project = Project::updateOrCreate(['slug' => $slug], ['organization_id' => $org->id, ...$attrs]);

        foreach ($repos as $repo) {
            $project->repositories()->updateOrCreate(['name' => $repo['name']], $repo);
        }

        foreach ($environments as $env) {
            $project->environments()->updateOrCreate(['name' => $env['name']], $env);
        }

        foreach ($decisions as $decision) {
            $project->decisions()->updateOrCreate(['title' => $decision['title']], $decision);
        }

        foreach ($knowledge as $item) {
            $project->knowledgeItems()->updateOrCreate(['title' => $item['title']], $item);
        }

        $this->auditIfNew($project);

        return $project;
    }

    private function auditIfNew(Project $project): void
    {
        if ($project->wasRecentlyCreated) {
            AuditEvent::record('owner', 'project.onboarded', $project, "{$project->name} catalogued into muchiri_os.");
        }
    }
}
