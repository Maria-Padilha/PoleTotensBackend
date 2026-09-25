<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            // Posição no ranking geral do evento no momento em que essa
            // jogada terminou — guardado na hora pra não precisar recalcular
            // histórico depois (o ranking muda com o tempo).
            $table->unsignedInteger('ranking_position')->nullable()->after('score');
            // Foto opcional pro recap: gera um token (vira QR code), o
            // convidado escaneia no celular, tira a foto, ela sobe pro R2 e
            // fica salva aqui.
            $table->string('photo_token')->nullable()->unique()->after('metadata');
            $table->string('photo_url')->nullable()->after('photo_token');
        });
    }

    public function down(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            $table->dropColumn(['ranking_position', 'photo_token', 'photo_url']);
        });
    }
};
