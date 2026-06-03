<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proprietaires', function (Blueprint $table) {
            $table->id();
            $table->enum('civilite', ['Monsieur', 'Madame', 'Société'])->nullable();
            $table->string('prenom');
            $table->string('nom');
            $table->string('nin')->nullable(); // Numéro d'identification nationale
            $table->string('ninea')->nullable(); // Pour les sociétés
            $table->string('cni_passport')->nullable();
            $table->string('telephone')->nullable();
            $table->text('adresse')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proprietaires');
    }
};
