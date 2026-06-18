<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\StatsService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class StatsServiceTest extends TestCase
{
    private function player(int $id, string $name, string $tier = 'B', string $role = 'rifle'): Player
    {
        $p = new Player(['name' => $name, 'tier' => $tier, 'role' => $role]);
        $p->id = $id;

        return $p;
    }

    /**
     * @param  array<int,array<int>>  $teams
     * @param  int|null  $winnerTeam  team index, or null for a draw
     */
    private function match(array $teams, ?int $winnerTeam): GameMatch
    {
        return new GameMatch(['teams' => $teams, 'winner_team' => $winnerTeam]);
    }

    /** @return array<int,array> keyed by player id for easy assertions */
    private function byId(array $leaderboard): array
    {
        return collect($leaderboard)->keyBy('id')->all();
    }

    public function test_empty_history_gives_zeroed_stats(): void
    {
        $players = collect([$this->player(1, 'Solo')]);

        $board = (new StatsService)->leaderboard($players, collect());

        $this->assertSame(0, $board[0]['games']);
        $this->assertSame(0, $board[0]['wins']);
        $this->assertSame(0.0, $board[0]['win_rate']);
        $this->assertSame(0, $board[0]['streak']);
    }

    public function test_computes_wins_games_and_win_rate(): void
    {
        $players = collect([
            $this->player(1, 'Alice'),
            $this->player(2, 'Bob'),
            $this->player(3, 'Cara'),
        ]);

        $matches = collect([
            $this->match([[1, 2], [3]], 0),     // 1 W, 2 W, 3 L
            $this->match([[1], [2, 3]], 1),      // 1 L, 2 W, 3 W
            $this->match([[1, 3], [2]], null),   // draw — all play, nobody wins
        ]);

        $board = $this->byId((new StatsService)->leaderboard($players, $matches));

        $this->assertSame([3, 1], [$board[1]['games'], $board[1]['wins']]); // Alice 1/3
        $this->assertSame([3, 2], [$board[2]['games'], $board[2]['wins']]); // Bob 2/3
        $this->assertSame([3, 1], [$board[3]['games'], $board[3]['wins']]); // Cara 1/3
        $this->assertEqualsWithDelta(2 / 3, $board[2]['win_rate'], 0.0001);
    }

    public function test_streak_tracks_recent_form_and_draws_are_transparent(): void
    {
        $players = collect([$this->player(1, 'Alice'), $this->player(2, 'Bob'), $this->player(3, 'Cara')]);

        $matches = collect([
            $this->match([[1, 2], [3]], 0),    // Alice W(+1), Bob W(+1), Cara L(-1)
            $this->match([[1], [2, 3]], 1),     // Alice L(-1), Bob W(+2), Cara W(+1)
            $this->match([[1, 3], [2]], null),  // draw — streaks unchanged
        ]);

        $board = $this->byId((new StatsService)->leaderboard($players, $matches));

        $this->assertSame(-1, $board[1]['streak']); // Alice: W then L → L1, draw transparent
        $this->assertSame(2, $board[2]['streak']);  // Bob: W,W → W2, draw transparent
        $this->assertSame(1, $board[3]['streak']);  // Cara: L,W → W1
    }

    public function test_sorted_by_win_rate_then_games_then_name(): void
    {
        $players = collect([
            $this->player(1, 'Alice'),
            $this->player(2, 'Bob'),
            $this->player(3, 'Cara'),
        ]);

        $matches = collect([
            $this->match([[1, 2], [3]], 0),
            $this->match([[1], [2, 3]], 1),
            $this->match([[1, 3], [2]], null),
        ]);

        $board = (new StatsService)->leaderboard($players, $matches);

        // Bob 2/3 first; Alice & Cara tie 1/3 + 3 GP, broken by name.
        $this->assertSame(['Bob', 'Alice', 'Cara'], array_column($board, 'name'));
    }

    public function test_ids_not_in_the_player_set_are_ignored(): void
    {
        $players = collect([$this->player(1, 'Alice')]);
        // Player 99 was deleted but still appears in history.
        $matches = collect([$this->match([[1], [99]], 0)]);

        $board = (new StatsService)->leaderboard($players, $matches);

        $this->assertCount(1, $board);
        $this->assertSame(1, $board[0]['games']);
        $this->assertSame(1, $board[0]['wins']);
    }
}
