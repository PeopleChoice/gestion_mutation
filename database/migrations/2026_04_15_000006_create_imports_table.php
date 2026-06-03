<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('nom_fichier');
            $table->string('fichier_path');
            $table->foreignId('projet_id')->constrained()->onDelete('cascade');
            $table->foreignId('imported_by')->constrained('users')->onDelete('cascade');
            $table->enum('statut', ['en_cours', 'termine', 'erreur'])->default('en_cours');
            $table->integer('total_lignes')->default(0);
            $table->integer('lignes_traitees')->default(0);
            $table->integer('lignes_matchees')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
