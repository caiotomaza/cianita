<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaults', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();
            $table->index('owner_id');
        });

        Schema::create('vault_user', function (Blueprint $table): void {
            $table->foreignId('vault_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('viewer');
            $table->timestamps();
            $table->primary(['vault_id', 'user_id']);
        });

        Schema::create('notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('vault_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['vault_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('vault_user');
        Schema::dropIfExists('vaults');
    }
};
