<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Define el pivot role_permission que conecta roles y permisos.
     */
    public function up(): void
    {
        Schema::create(famiq_permission_table_name('role_permission'), function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);

            if (config('famiq-permission.use_foreign_keys', true)) {
                $table->foreign('role_id')
                    ->references('id')
                    ->on(famiq_permission_table_name('roles'))
                    ->onDelete('cascade');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on(famiq_permission_table_name('permissions'))
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Elimina el pivot role_permission.
     */
    public function down(): void
    {
        Schema::dropIfExists(famiq_permission_table_name('role_permission'));
    }
};
