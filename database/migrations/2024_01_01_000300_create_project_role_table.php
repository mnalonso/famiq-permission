<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(famiq_permission_table_name('project_role'), function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('role_id');
            $table->unique(['project_id', 'role_id']);

            if (config('famiq-permission.use_foreign_keys', true)) {
                $table->foreign('project_id')
                    ->references('id')
                    ->on(config('famiq-permission.tables.projects', 'projects'))
                    ->onDelete('cascade');

                $table->foreign('role_id')
                    ->references('id')
                    ->on(famiq_permission_table_name('roles'))
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(famiq_permission_table_name('project_role'));
    }
};
