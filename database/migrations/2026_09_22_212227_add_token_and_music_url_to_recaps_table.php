<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recaps', function (Blueprint $table) {
            // Link público pra abrir o recap (na TV do evento, no totem, ou
            // mandar pro cliente) sem precisar estar logado.
            $table->string('token')->nullable()->unique()->after('event_id');
            $table->string('music_url')->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('recaps', function (Blueprint $table) {
            $table->dropColumn(['token', 'music_url']);
        });
    }
};
