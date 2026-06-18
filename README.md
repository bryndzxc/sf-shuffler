# SF Shuffler

A tier-balanced team shuffler for the **Special Force Alpha** clan. Maintain a roster with skill tiers, mark who showed up tonight, shuffle everyone into balanced 5-man teams, record results, and watch win rates and MMR build up over time.

Built as a clan-shared web app (one database, real history) rather than a one-device tool — the roster, match history, and stats are shared across everyone who opens it.

---

## Features

- **Roster** — add players with a skill **tier** (S / A / B / C) and **role** (rifle / sniper). Cycle tier/role inline, rename, delete, and toggle who's **READY** for tonight. Capped at 50 players. Callsigns are unique (case-insensitive).
- **Deploy (shuffle)** — forms balanced **5-man teams** (1 sniper + 4 rifles) from everyone marked ready. The number of teams is derived automatically; teams pair into 2-team **games**, with leftovers shown as **reserves** or a **bye**. Re-shuffle for variety and **copy a clean summary for Discord**.
- **Match recording** — log each game's winner (or a draw) one at a time. Results are server-authoritative and stored permanently.
- **MMR / rating** — every player has a match rating that **moves with results** (see below), blended with their tier to keep team balancing fair as the season goes on.
- **Leaderboards (Intel)** — win rate, games played, current streak, and MMR per player.
- **History** — every recorded match, newest first, paginated.
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

### Tiers & power

Tiers carry a weight used for balancing: **S = 4, A = 3, B = 2, C = 1**.

### Shuffle engine (`App\Services\ShuffleService`)

Forms fixed **5-man teams (1 sniper + 4 rifles)**. The team count is *derived, not chosen*:
`min(snipers, ⌊rifles ÷ 4⌋, 10)`. Each attempt seeds one sniper per team plus a random subset of rifles, then drops the strongest remaining rifles onto the weakest team with an open slot. It runs ~60 attempts and keeps the split with the lowest power **spread** (max − min team), avoiding an exact repeat of the previous shuffle. Players who don't fit a full team become **reserves**.

`ShuffleController` then pairs teams 0&1, 2&3, … into 2-team games (an odd team out becomes a **bye**).

### MMR (`App\Services\MmrService`)

Each player's rating is **derived by replaying the match history** (never stored on the player), so it's always accurate and survives edits.

- **Seeded from tier:** S = 1000, A = 800, B = 650, C = 500 (S sits well clear so it keeps weight).
- **Fixed deltas:** win **+25**, loss **−15**, draw **+5**, clamped at a **floor of 100** (a loss can never push below it).
- **Blended into the shuffle:** balancing power = `0.4 · tierSeed + 0.6 · mmr`, so a player's manual tier keeps a permanent anchor while results drift the rest.

All the knobs (deltas, floor, seeds, blend ratio) are constants at the top of `MmrService`.

### Stats (`App\Services\StatsService`)

Win rate, games, and streaks are computed live from the `matches` table in one pass — never stored on the player, so a freshly recorded result is reflected immediately. A draw counts as a game but leaves the streak unchanged.

---

## Data model

**players** — `name` (unique callsign), `tier` (S/A/B/C), `role` (rifle/sniper), `present` (ready toggle), timestamps.

**matches** — `teams` (JSON: array of player-id arrays), `powers` (JSON: parallel power totals), `winner_team` (team index, **null = draw**), `played_at`, timestamps. Each row is one 2-team game. *(The model is `App\Models\GameMatch` — `Match` is a reserved word in PHP 8.)*

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
#   then set DB_DATABASE=sf_shuffler and your MySQL credentials in .env

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
- **Derive, don't store** — win rate, streaks, and MMR are all computed from the `matches` table, never cached on the player.
- **Tier data lives in two places** — backend `Player::TIER_WEIGHTS` / `::TIERS` and frontend `resources/js/tiers.js`; keep them in sync.
- **Theme** — dark tactical: near-black background, amber accent, Oswald headings; tier colors S red, A amber, B steel blue, C grey.

---

## Roadmap

- [x] Roster
- [x] Shuffle engine
- [x] Match recording
- [x] Stats / leaderboard
- [x] MMR rating
- [ ] **Auth gating** — restrict roster/match edits to officers, everyone else views
- [ ] Configurable team size / composition
- [ ] Tournament bracket + wheel-of-names
- [ ] Deploy to a shared URL for the whole clan
