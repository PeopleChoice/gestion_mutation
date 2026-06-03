<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_lignes', function (Blueprint $table) {
            $table->string('type_piece')->nullable()->after('nom');
            $table->string('code_paye')->nullable()->after('type_piece');
            $table->string('cni_passport')->nullable()->after('code_paye');
        });
    }

    public function down(): void
    {
        Schema::table('import_lignes', function (Blueprint $table) {
            $table->dropColumn(['type_piece', 'code_paye', 'cni_passport']);
        });
    }
};
