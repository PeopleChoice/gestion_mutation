<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelles', function (Blueprint $table) {
            $table->id();
            $table->string('numero_lot'); // ex: 3900, AT xxx
            $table->foreignId('projet_id')->constrained()->onDelete('cascade');
            $table->foreignId('proprietaire_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ref_lettre')->nullable();
            $table->date('date_attribution')->nullable();
            $table->decimal('superficie', 10, 2)->nullable();
            $table->string('usage')->nullable(); // habitation, commercial, etc.
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->unique(['numero_lot', 'projet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelles');
    }
};
