<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(famiq_permission_table_name('user_role'), function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unique(['user_id', 'role_id', 'project_id']);

            if (config('famiq-permission.use_foreign_keys', true)) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on(config('famiq-permission.tables.users', 'users'))
                    ->onDelete('cascade');

                $table->foreign('role_id')
                    ->references('id')
                    ->on(famiq_permission_table_name('roles'))
                    ->onDelete('cascade');

                $table->foreign('project_id')
                    ->references('id')
                    ->on(config('famiq-permission.tables.projects', 'projects'))
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(famiq_permission_table_name('user_role'));
    }
};
