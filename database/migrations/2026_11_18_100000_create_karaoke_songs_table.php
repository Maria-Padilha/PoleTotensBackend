<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Biblioteca de músicas do karaokê: título, artista, letra sincronizada e o link do
        // áudio (um único arquivo no R2, reaproveitado por todos os eventos que usarem a música).
        Schema::create('karaoke_songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('audio_url', 2048);
            $table->longText('lyrics')->nullable();
            $table->decimal('offset', 4, 1)->default(0);
            $table->string('color', 7)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karaoke_songs');
    }
};
