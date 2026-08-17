<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id')->comment('e.g. "ulthane", "valkyrie"');
            $table->string('source')->comment('which machine this run happened on, e.g. "mac", "vps"');
            $table->string('session_id')->comment('OpenClaw session/run id, used for dedup on re-sync');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->string('model_provider')->nullable();
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status')->default('unknown')->comment('ok, error, timeout, unknown');
            $table->string('triggered_by')->nullable()->comment('cron, whatsapp, manual, etc.');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'session_id']);
            $table->index(['agent_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
    }
};
