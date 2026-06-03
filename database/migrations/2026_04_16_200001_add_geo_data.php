<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fichier DXF et GeoJSON global du projet
        Schema::table('projets', function (Blueprint $table) {
            $table->string('fichier_dxf')->nullable()->after('longitude');
            $table->json('geojson')->nullable()->after('fichier_dxf');
        });

        // Coordonnées/contour de chaque parcelle
        Schema::table('parcelles', function (Blueprint $table) {
            $table->json('geometrie')->nullable()->after('observation'); // polygone GeoJSON
            $table->decimal('centroid_lat', 10, 7)->nullable()->after('geometrie');
            $table->decimal('centroid_lng', 10, 7)->nullable()->after('centroid_lat');
        });
    }

    public function down(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            $table->dropColumn(['fichier_dxf', 'geojson']);
        });
        Schema::table('parcelles', function (Blueprint $table) {
            $table->dropColumn(['geometrie', 'centroid_lat', 'centroid_lng']);
        });
    }
};
