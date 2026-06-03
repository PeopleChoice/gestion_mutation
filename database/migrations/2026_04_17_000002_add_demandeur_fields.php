<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_lignes', function (Blueprint $table) {
            $table->string('demandeur_prenom')->nullable()->after('cni_passport');
            $table->string('demandeur_nom')->nullable()->after('demandeur_prenom');
            $table->string('demandeur_telephone')->nullable()->after('demandeur_nom');
        });

        Schema::table('mutations', function (Blueprint $table) {
            $table->string('demandeur_prenom')->nullable()->after('type_piece');
            $table->string('demandeur_nom')->nullable()->after('demandeur_prenom');
            $table->string('demandeur_telephone')->nullable()->after('demandeur_nom');
        });
    }

    public function down(): void
    {
        Schema::table('import_lignes', function (Blueprint $table) {
            $table->dropColumn(['demandeur_prenom', 'demandeur_nom', 'demandeur_telephone']);
        });
        Schema::table('mutations', function (Blueprint $table) {
            $table->dropColumn(['demandeur_prenom', 'demandeur_nom', 'demandeur_telephone']);
        });
    }
};
