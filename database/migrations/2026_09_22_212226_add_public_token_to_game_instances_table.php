<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_instances', function (Blueprint $table) {
            // Diferente do preview_token (expira em 5min, é pra aprovação do
            // organizador): o public_token é permanente — é o link real que o
            // totem abre no navegador em modo kiosk pra convidados jogarem.
            $table->string('public_token')->nullable()->unique()->after('preview_token');
        });
    }

    public function down(): void
    {
        Schema::table('game_instances', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
