<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor')->comment('e.g. "owner", "LegalLine Engineer Agent"');
            $table->string('action')->comment('e.g. "project.created", "repository.attached"');
            $table->nullableMorphs('subject');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('result', ['success', 'failure'])->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
