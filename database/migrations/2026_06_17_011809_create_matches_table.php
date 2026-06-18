<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->json('team_a');             // array of player ids
            $table->json('team_b');
            $table->enum('winner', ['a', 'b', 'draw'])->nullable(); // null until recorded
            $table->unsignedInteger('power_a'); // tier-weight snapshot at play time
            $table->unsignedInteger('power_b');
            $table->timestamp('played_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
