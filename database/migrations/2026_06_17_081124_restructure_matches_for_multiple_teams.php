<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move from a fixed two-team shape (team_a/team_b/winner enum) to an
     * N-team shape: teams[] of id-arrays, powers[] of ints, winner_team index
     * (null = draw).
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->json('teams')->nullable()->after('id');
            $table->json('powers')->nullable()->after('teams');
            $table->unsignedTinyInteger('winner_team')->nullable()->after('powers');
        });

        foreach (DB::table('matches')->get() as $m) {
            DB::table('matches')->where('id', $m->id)->update([
                'teams' => json_encode([
                    json_decode($m->team_a, true) ?? [],
                    json_decode($m->team_b, true) ?? [],
                ]),
                'powers' => json_encode([(int) $m->power_a, (int) $m->power_b]),
                'winner_team' => match ($m->winner) {
                    'a' => 0,
                    'b' => 1,
                    default => null, // draw or unrecorded
                },
            ]);
        }

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['team_a', 'team_b', 'power_a', 'power_b', 'winner']);
        });
    }

    /** Restores the two-team shape (lossy for matches with 3+ teams). */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->json('team_a')->nullable();
            $table->json('team_b')->nullable();
            $table->unsignedInteger('power_a')->default(0);
            $table->unsignedInteger('power_b')->default(0);
            $table->enum('winner', ['a', 'b', 'draw'])->nullable();
        });

        foreach (DB::table('matches')->get() as $m) {
            $teams = json_decode($m->teams, true) ?? [[], []];
            $powers = json_decode($m->powers, true) ?? [0, 0];
            DB::table('matches')->where('id', $m->id)->update([
                'team_a' => json_encode($teams[0] ?? []),
                'team_b' => json_encode($teams[1] ?? []),
                'power_a' => $powers[0] ?? 0,
                'power_b' => $powers[1] ?? 0,
                'winner' => is_null($m->winner_team) ? 'draw' : ($m->winner_team === 0 ? 'a' : 'b'),
            ]);
        }

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['teams', 'powers', 'winner_team']);
        });
    }
};
