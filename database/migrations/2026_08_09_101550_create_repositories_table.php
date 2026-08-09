<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('provider', ['github', 'gitlab', 'other'])->default('github');
            $table->string('repository')->comment('e.g. mobipine/mp_tenthline_api');
            $table->enum('role', ['frontend', 'backend', 'infrastructure', 'other'])->default('other');
            $table->string('local_path')->nullable();
            $table->string('default_branch')->default('main');
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
