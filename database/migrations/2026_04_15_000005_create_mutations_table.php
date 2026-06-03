<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcelle_id')->constrained()->onDelete('cascade');
            $table->foreignId('ancien_proprietaire_id')->nullable()->constrained('proprietaires')->onDelete('set null');
            $table->foreignId('nouveau_proprietaire_id')->nullable()->constrained('proprietaires')->onDelete('set null');
            $table->foreignId('import_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('statut', ['en_attente', 'validee', 'refusee', 'annulee'])->default('en_attente');
            $table->string('ref_lettre')->nullable();
            $table->string('numero_notification')->nullable(); // N°0004856
            $table->date('date_mutation')->nullable();
            $table->text('motif_refus')->nullable();
            $table->text('observation')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutations');
    }
};
