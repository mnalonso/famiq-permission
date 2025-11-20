<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Crea la tabla de permisos globales y por proyecto.
     */
    public function up(): void
    {
        Schema::create(famiq_permission_table_name('permissions'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('order')->default(0);
            $table->string('type', 32)->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->timestamps();

            if (config('famiq-permission.use_foreign_keys', true)) {
                $table->foreign('project_id')
                    ->references('id')
                    ->on(config('famiq-permission.tables.projects', 'projects'))
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Elimina la tabla de permisos.
     */
    public function down(): void
    {
        Schema::dropIfExists(famiq_permission_table_name('permissions'));
    }
};
