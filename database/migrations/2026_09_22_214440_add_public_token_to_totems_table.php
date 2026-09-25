<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('totems', function (Blueprint $table) {
            // Link fixo que fica aberto no navegador do totem em modo kiosk —
            // escolhe o evento vinculado, mostra todos os jogos disponíveis.
            $table->string('public_token')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('totems', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
