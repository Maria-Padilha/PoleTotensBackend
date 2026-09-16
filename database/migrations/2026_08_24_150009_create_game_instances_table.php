<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_totem_id')->constrained('event_totem')->cascadeOnDelete();
            $table->foreignId('user_game_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('config')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('preview_token')->nullable()->unique();
            $table->timestamp('preview_opened_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_instances');
    }
};
