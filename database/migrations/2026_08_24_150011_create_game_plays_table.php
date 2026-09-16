<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_name')->nullable();
            $table->integer('score')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('client_uuid')->nullable()->unique();
            $table->timestamp('played_at');
            $table->timestamps();

            $table->index(['game_instance_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_plays');
    }
};
