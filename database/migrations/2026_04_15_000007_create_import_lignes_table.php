<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->onDelete('cascade');
            $table->integer('numero_ordre');
            $table->string('civilite')->nullable();
            $table->string('prenom')->nullable();
            $table->string('nom')->nullable();
            $table->string('nin')->nullable();
            $table->string('ninea')->nullable();
            $table->string('telephone')->nullable();
            $table->string('numero_lot');
            $table->string('ref_lettre')->nullable();
            $table->string('date_excel')->nullable();
            $table->string('observation')->nullable();
            $table->string('precedent_attributaire')->nullable();
            $table->foreignId('parcelle_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('matched')->default(false);
            $table->enum('statut', ['en_attente', 'validee', 'refusee'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_lignes');
    }
};
