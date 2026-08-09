<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('assigned_agent', ['ulthane', 'valkyrie', 'samael', 'raziel', 'morrigan', 'thoth', 'argus']);
            $table->enum('status', ['pending', 'in_progress', 'done', 'blocked'])->default('pending');
            $table->string('created_by')->comment('e.g. "valkyrie", "owner"');
            $table->text('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_agent', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
