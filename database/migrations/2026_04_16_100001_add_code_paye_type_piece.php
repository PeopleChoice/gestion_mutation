<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->string('type_piece')->nullable()->after('cni_passport'); // CNI, Passeport, Carte consulaire, etc.
        });

        Schema::table('mutations', function (Blueprint $table) {
            $table->string('code_paye')->nullable()->after('ref_lettre');
            $table->string('type_piece')->nullable()->after('code_paye');
        });
    }

    public function down(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropColumn('type_piece');
        });
        Schema::table('mutations', function (Blueprint $table) {
            $table->dropColumn(['code_paye', 'type_piece']);
        });
    }
};
