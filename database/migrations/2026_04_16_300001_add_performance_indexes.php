<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutations', function (Blueprint $table) {
            $table->index('statut');
            $table->index('date_mutation');
            $table->index('numero_notification');
        });

        Schema::table('parcelles', function (Blueprint $table) {
            $table->index('numero_lot');
        });

        Schema::table('proprietaires', function (Blueprint $table) {
            $table->index('nom');
            $table->index('cni_passport');
            $table->index('nin');
            $table->index('telephone');
        });

        Schema::table('import_lignes', function (Blueprint $table) {
            $table->index('statut');
            $table->index('numero_lot');
        });
    }

    public function down(): void
    {
        Schema::table('mutations', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropIndex(['date_mutation']);
            $table->dropIndex(['numero_notification']);
        });
        Schema::table('parcelles', function (Blueprint $table) {
            $table->dropIndex(['numero_lot']);
        });
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropIndex(['nom']);
            $table->dropIndex(['cni_passport']);
            $table->dropIndex(['nin']);
            $table->dropIndex(['telephone']);
        });
        Schema::table('import_lignes', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropIndex(['numero_lot']);
        });
    }
};
