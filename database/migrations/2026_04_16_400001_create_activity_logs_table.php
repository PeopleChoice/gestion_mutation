<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // login, mutation_validee, import, etc.
            $table->string('module'); // auth, mutation, import, parcelle, projet, etc.
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->json('details')->nullable(); // données supplémentaires
            $table->timestamps();

            $table->index('action');
            $table->index('module');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
