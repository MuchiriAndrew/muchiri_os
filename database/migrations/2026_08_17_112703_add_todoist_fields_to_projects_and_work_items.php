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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('todoist_project_id')->nullable()->comment('Todoist project this maps to, if synced');
        });

        Schema::table('work_items', function (Blueprint $table) {
            $table->string('todoist_task_id')->nullable()->comment('Mirrored Todoist task, if synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('todoist_project_id');
        });

        Schema::table('work_items', function (Blueprint $table) {
            $table->dropColumn('todoist_task_id');
        });
    }
};
