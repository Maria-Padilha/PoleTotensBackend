<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            $table->string('recording_token', 64)->nullable()->after('lead_id');
            $table->string('recording_url', 2048)->nullable()->after('recording_token');
        });
    }

    public function down(): void
    {
        Schema::table('game_plays', function (Blueprint $table) {
            $table->dropColumn(['recording_token', 'recording_url']);
        });
    }
};
