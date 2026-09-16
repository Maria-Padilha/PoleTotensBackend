<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_totem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('totem_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'finished'])->default('pending');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'totem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_totem');
    }
};
