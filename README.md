# SF Shuffler

A tier-balanced team shuffler for the **Special Force Alpha** clan. Maintain a roster with skill tiers, mark who showed up tonight, shuffle everyone into balanced 5-man teams, record results, and watch win rates and MMR build up over time.

Built as a clan-shared web app (one database, real history) rather than a one-device tool — the roster, match history, and stats are shared across everyone who opens it.

---

## Features

- **Roster** — add players by **callsign** + **role** (rifle / sniper). Everyone starts at tier **C** and earns their tier from match results — there's no manual tier picker. Rename, toggle who's **READY**, and (admins only) delete. Capped at 50 players. Callsigns are unique (case-insensitive).
- **Deploy (shuffle)** — forms balanced **5-man teams** (1 sniper + 4 rifles) from everyone marked ready. The number of teams is derived automatically; teams pair into 2-team **games**, with leftovers shown as **reserves** or a **bye**. Re-shuffle for variety and **copy a clean summary for Discord**.
- **Best-of-N series + maps** — each game runs a **Bo3 / Bo5** series: the app rolls maps from the SF map pool (with per-map re-roll), you record each map's winner, and it tracks the series score and declares the winner. Every map counts toward MMR.
- **MMR / auto-tiers** — every player has a match rating that **moves with results** (see below); their **tier is derived from that MMR**, so players auto-level up and down over the season. Team balancing uses MMR directly.
- **Leaderboards (Intel)** — win rate, games played, current streak, MMR, and tier per player.
- **History** — every recorded match (with its map), newest first, paginated.
- **Hidden admin gate** — a single-password `/admin/login` unlocks deleting players; everything else is open to the clan.
- **Dark tactical theme** — near-black UI, amber accent, tier color-coding, responsive (desktop sidebar / mobile tab bars).

---

## Tech stack

| Layer | Choice |
|-------|--------|
| Backend | **Laravel 13** (PHP 8.3+) |
| Frontend | **Inertia.js + React** (no separate API layer — controllers return Inertia responses) |
| Styling | **Tailwind CSS** + inline tactical styling |
| Build | **Vite** |
| Database | **MySQL 8.0** (SQLite `:memory:` for tests) |
| Auth scaffold | **Laravel Breeze** (react preset) |

---

## How it works

### Tiers (derived from MMR)

There's no stored tier. A player's tier is computed from their current MMR: **C** below 650, **B** at 650+, **A** at 800+, **S** at 1000+. Everyone starts at C and levels up (or down) as their rating moves. Tiers are display + color only; the shuffle balances on MMR.

### Shuffle engine (`App\Services\ShuffleService`)

Forms fixed **5-man teams (1 sniper + 4 rifles)**. The team count is *derived, not chosen*:
`min(snipers, ⌊rifles ÷ 4⌋, 10)`. Each attempt seeds one sniper per team plus a random subset of rifles, then drops the strongest remaining rifles onto the weakest team with an open slot. It runs ~60 attempts and keeps the split with the lowest power **spread** (max − min team), avoiding an exact repeat of the previous shuffle. Players who don't fit a full team become **reserves**.

`ShuffleController` then pairs teams 0&1, 2&3, … into 2-team games (an odd team out becomes a **bye**).

### MMR (`App\Services\MmrService`)

Each player's rating is **derived by replaying the match history** (never stored on the player), so it's always accurate and survives edits.

- **Flat start:** everyone seeds at **500** (the C baseline) — no per-tier seeding.
- **Fixed deltas:** win **+25**, loss **−15**, draw **+5**, clamped at a **floor of 100** (a loss can never push below it).
- **Tier comes from MMR** (`tierForMmr`), and the shuffle balances on MMR directly.

All the knobs (deltas, floor, seed, tier thresholds) are constants at the top of `MmrService`. To rebaseline production (everyone back to C, past winners keep their +25 per win), just run the app on this model — `php artisan roster:ratings` prints the recomputed table.

### Stats (`App\Services\StatsService`)

Win rate, games, and streaks are computed live from the `matches` table in one pass — never stored on the player, so a freshly recorded result is reflected immediately. A draw counts as a game but leaves the streak unchanged.

---

## Data model

**players** — `name` (unique callsign), `role` (rifle/sniper), `present` (ready toggle), timestamps. *(No `tier` column — tier is derived from MMR.)*

**matches** — `teams` (JSON: array of player-id arrays), `powers` (JSON: parallel power totals), `winner_team` (team index, **null = draw**), `map` (nullable), `played_at`, timestamps. Each row is one 2-team game (one map of a series). *(The model is `App\Models\GameMatch` — `Match` is a reserved word in PHP 8.)*

**team_names** — optional per-slot overrides for the default NATO team labels (Alpha, Bravo, …).

---

## Local setup (Laragon / Windows)

> ⚠️ The PHP on PATH may be too old for Laravel 13. Put **PHP 8.3** first on PATH so Composer/Artisan subprocesses use it:
> ```bash
> export PATH="/e/laragon/bin/php/php-8.3.22-Win32-vs16-x64:$PATH"
> ```

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
#   then set DB_DATABASE=sf_shuffler and your MySQL credentials in .env,
#   and ADMIN_PASSWORD=... for the hidden /admin/login gate

# 3. Database
php artisan migrate

# 4. Run (two terminals)
php artisan serve     # Laravel on http://127.0.0.1:8000
npm run dev           # Vite HMR
```

For a production bundle: `npm run build`.

---

## Tests

Feature tests run on SQLite in-memory; keep controller queries DB-agnostic.

```bash
php artisan test
```

Covers the shuffle engine, MMR, stats, roster CRUD, match recording, and team names.

---

## Project conventions

- **Inertia, not REST** — controllers render Inertia pages; there's no separate API layer.
- **DB-first** — the clan-shared roster, real match history, and persistent stats are the whole point; nothing important lives in the browser.
- **Derive, don't store** — win rate, streaks, MMR, **and tier** are all computed from the `matches` table, never cached on the player.
- **Theme** — dark tactical: near-black background, amber accent, Oswald headings; tier colors S red, A amber, B steel blue, C grey.

---

## Roadmap

- [x] Roster
- [x] Shuffle engine
- [x] Match recording
- [x] Stats / leaderboard
- [x] MMR rating + auto-derived tiers
- [x] Admin gate (delete is admin-only)
- [x] Maps + Best-of-N series tracker
- [ ] Configurable team size / composition
- [ ] Tournament bracket + wheel-of-names
- [ ] Deploy to a shared URL for the whole clan
