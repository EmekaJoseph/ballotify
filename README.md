<p align="center">
  <img src="frontend/public/ballotify-logo.png" width="96" alt="Ballotify logo" />
</p>

<h1 align="center">Ballotify</h1>

<p align="center">
  Online elections, awards and polls with one-time voter codes, a mobile-friendly ballot and live results.
</p>

---

## What it does

| For organisers                                                     | For voters                             |
| ------------------------------------------------------------------ | -------------------------------------- |
| Create events with categories and candidates (with photos)         | Open one link on any phone or laptop   |
| Choose **single choice** (one per category) or **multiple choice** | Enter a personal one-time code         |
| Schedule when voting opens and closes, or close it early           | Pick candidates, review, submit        |
| Generate thousands of voter codes, or import a name list           | Confirmation screen, then live results |
| Export codes to CSV, or print voter slips with a QR code           |                                        |
| Track turnout, re-enable a code, share a public results page       |                                        |

**Integrity guarantees, all enforced on the server:**

- Each code can vote **exactly once**. The voter row is locked inside a transaction, so two simultaneous submits can't both succeed.
- Ballots outside the voting window are rejected.
- Single-choice rules are checked server side, and candidates must belong to the category and event.
- Code guessing is rate-limited per network and event.
- Public results show totals only, never who voted for whom.

## Tech stack

| Layer        | Technology                                                                       |
| ------------ | -------------------------------------------------------------------------------- |
| API          | Laravel 13 (PHP 8.3+), Sanctum token auth, Pest tests                            |
| Database     | MySQL / MariaDB (SQLite works for local dev and tests)                           |
| Frontend     | Nuxt 4 (Vue 3, TypeScript) SPA, Pinia, Bootstrap 5 grid + custom design system   |
| Live results | Pusher via Laravel broadcasting + Laravel Echo (optional; falls back to polling) |

## Project structure

```
ballotify/
├── backend/                 Laravel API
│   ├── app/Http/Controllers/Api/   Auth, Event, Category, Candidate, Voter, Vote
│   ├── app/Policies/EventPolicy.php  organisers can only touch their own events
│   ├── app/Events/VoteCast.php       live-results broadcast
│   ├── database/migrations/          schema (users, events, categories, candidates, voters, votes)
│   ├── database/seeders/             demo data (admin@example.com / password)
│   ├── docs/API.md                   endpoint reference
│   └── tests/Feature/                voting-integrity and admin tests
├── frontend/                Nuxt app
│   └── app/
│       ├── pages/            landing, auth, dashboard, /vote/:token, /event/:token/results, demos
│       ├── components/       BallotForm, ResultsBoard, event/* (dashboard tabs), UI kit
│       ├── api/              typed axios client
│       ├── composables/      toasts, confirm dialog, countdown, links
│       └── assets/css/main.css  design tokens and components
└── docs/HOW_IT_WORKS.txt    illustrated walkthrough of the whole process
```

## Quick start (local)

**Requirements:** PHP 8.3+ with `pdo_mysql` (or `pdo_sqlite`), Composer 2, Node 20+ (22 recommended), and MySQL/MariaDB.

### 1. API

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# edit .env: DB_DATABASE / DB_USERNAME / DB_PASSWORD
php artisan migrate --seed        # creates tables + demo events
php artisan serve --port=8883
```

The demo login is `admin@example.com` / `password`. The demo voter codes are written to `storage/app/private/seed_voter_codes.json` (the first event's codes look like `E1-0150`).

> **Upgrading from an older copy?** The voting tables were consolidated into one migration. Run `php artisan migrate:fresh --seed` on a development database. This **wipes its data**.

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env              # points at http://localhost:8883/api by default
npm run dev                       # http://localhost:3000
```

### 3. Try it

1. Open http://localhost:3000 and sign in.
2. Open **Campus Election**, go to the **Share** tab and open the voting link.
3. Vote with a code from the **Voters** tab, then watch the results update.

## Configuration

### Backend `.env`

| Key                                                                          | Purpose                                                      |
| ---------------------------------------------------------------------------- | ------------------------------------------------------------ |
| `APP_URL`                                                                    | Public URL of the API. Used for candidate photo URLs.        |
| `FRONTEND_URL`                                                               | Public URL of the Nuxt site. Used for voting links and CORS. |
| `CORS_ALLOWED_ORIGINS`                                                       | Optional comma-separated extra origins.                      |
| `BROADCAST_CONNECTION`                                                       | `pusher` for instant live results, `log` to disable.         |
| `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` | Pusher credentials (free tier is fine).                      |

### Frontend `.env`

| Key                          | Purpose                                                                      |
| ---------------------------- | ---------------------------------------------------------------------------- |
| `NUXT_PUBLIC_API_BASE`       | API base URL, e.g. `https://api.example.com/api`                             |
| `NUXT_PUBLIC_PUSHER_KEY`     | Pusher key (same as `PUSHER_APP_KEY`). Leave empty to use 15-second polling. |
| `NUXT_PUBLIC_PUSHER_CLUSTER` | Pusher cluster, e.g. `eu`                                                    |

## Testing

```bash
cd backend  && php artisan test                                    # 23 feature tests
cd frontend && npx nuxi prepare && npx vue-tsc --noEmit -p .nuxt/tsconfig.app.json   # type check
cd frontend && npm run build                                       # production build
```

## Deploying

**API.** Deploy it like any Laravel app:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and the real `APP_URL` and `FRONTEND_URL`.
2. Run `composer install --no-dev -o`, `php artisan migrate --force` and `php artisan config:cache route:cache`.
3. Point the web root at `backend/public`, and make sure `public/candidates/` is writable for photos.

**Frontend.** It's a static SPA:

1. Build with `NUXT_PUBLIC_API_BASE=https://your-api/api npx nuxi generate`.
2. Upload `.output/public` to any static host (Netlify, Vercel, S3, cPanel…).
3. Serve `index.html` for unknown routes (SPA fallback) so links like `/vote/<token>` work.

## API reference

See [backend/docs/API.md](backend/docs/API.md).

## License

Proprietary. All rights reserved.
