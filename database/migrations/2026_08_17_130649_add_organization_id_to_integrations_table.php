<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropUnique(['provider']);
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['organization_id', 'provider']);
        });

        // The one integration connected so far was a work (Belva Digital) Todoist
        // account -- assign it explicitly rather than leaving it as an implicit
        // global fallback that other organizations would silently inherit.
        $belvaDigitalId = DB::table('organizations')->where('slug', 'belva-digital')->value('id');

        if ($belvaDigitalId) {
            DB::table('integrations')
                ->where('provider', 'todoist')
                ->whereNull('organization_id')
                ->update(['organization_id' => $belvaDigitalId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'provider']);
            $table->dropConstrainedForeignId('organization_id');
            $table->unique('provider');
        });
    }
};
