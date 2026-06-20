<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier is now derived from MMR (see App\Services\MmrService::tierForMmr), so the
 * stored column is retired. Players seed at a flat C-tier baseline and climb.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->enum('tier', ['S', 'A', 'B', 'C'])->default('C')->after('name');
        });
    }
};
