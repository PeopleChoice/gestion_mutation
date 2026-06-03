<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type')->default('notification_attribution'); // type de document
            $table->text('entete_html'); // En-tête configurable
            $table->text('corps_html'); // Corps du document avec placeholders
            $table->text('pied_html')->nullable(); // Pied de page
            $table->string('centre_fiscal')->nullable(); // ex: Centre des Services Fiscaux de TIVAOUANE
            $table->string('bureau')->nullable(); // ex: Bureau des Domaines
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
