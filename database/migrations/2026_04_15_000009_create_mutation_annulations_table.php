<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutation_annulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutation_id')->constrained()->onDelete('cascade');
            $table->foreignId('demande_par')->constrained('users')->onDelete('cascade');
            $table->foreignId('approuve_par')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('statut', ['en_attente', 'approuvee', 'rejetee'])->default('en_attente');
            $table->text('motif');
            $table->text('motif_rejet')->nullable();
            $table->timestamp('approuve_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutation_annulations');
    }
};
