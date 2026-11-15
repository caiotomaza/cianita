<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('note_id')->constrained('notes')->restrictOnDelete();
            $table->foreignId('vault_id')->constrained('vaults')->restrictOnDelete();
            $table->string('operation', 12);
            $table->unsignedInteger('base_version');
            $table->unsignedInteger('version');
            $table->string('title', 180);
            $table->char('sha256', 64);
            $table->string('status', 20)->default('prepared');
            $table->timestamps();
            $table->index(['note_id', 'status']);
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->foreignUuid('revision_id')->nullable()
                ->constrained('note_revisions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Não apagar o histórico de revisões durante rollback rotineiro.
        if (Schema::hasTable('note_revisions') && DB::table('note_revisions')->exists()) {
            throw new RuntimeException('Revisoes existentes: restaure backup pareado antes de reverter o esquema.');
        }
        Schema::table('notes', fn (Blueprint $table) => $table->dropConstrainedForeignId('revision_id'));
        Schema::dropIfExists('note_revisions');
    }
};
