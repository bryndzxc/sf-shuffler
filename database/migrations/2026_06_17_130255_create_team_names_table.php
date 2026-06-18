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
        // One row per renamed team slot (0-based). Slots without a row fall
        // back to the NATO default (Alpha, Bravo, …) on the frontend.
        Schema::create('team_names', function (Blueprint $table) {
            $table->unsignedTinyInteger('slot')->primary();
            $table->string('name', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_names');
    }
};
