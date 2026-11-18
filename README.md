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
| Create events with categories and candidates (photos, short descriptions) | Open one link on any phone or laptop   |
| Choose **single choice** or **multiple choice**, with an optional "pick up to N" per category | Enter a personal one-time code         |
| Schedule when voting opens and closes, or close it early           | Pick candidates, review, submit        |
| Generate thousands of voter codes, or import a name list           | Confirmation screen, then live results |
| Export codes to CSV, or print voter slips with a QR code           |                                        |
| Email or text codes to voters; track who has voted                 | Results page (when the organiser publishes it) |
| Choose when results are public: live, after close, or never        |                                        |
| Import candidates from a spreadsheet; duplicate an event            |                                        |
| Results as CSV, or a printable signed report (save as PDF)         |                                        |
| Activity log of every change; two-step login; dark mode            |                                        |
| **Platform admin:** see every organiser and event, suspend abuse    |                                        |

**Integrity guarantees, all enforced on the server:**

- **Secret ballot.** Only a running total per candidate is stored, with no record of who voted for whom: no ballot rows, timestamps or IPs. Not even the organiser or a database administrator can link a vote to a voter.
- Each code can vote **exactly once**. The voter row is locked inside a transaction, so two simultaneous submits can't both succeed. A code that has voted can't be reset.
- **The ballot locks at the first vote.** Categories and candidates can't be added, removed or moved. Name and photo fixes are still allowed, but each is recorded in the event's activity log, and the public results page shows how many changes were made during voting.
- Ballots outside the voting window are rejected.
- Single-choice rules are checked server side, and candidates must belong to the category and event.
- Code guessing is rate-limited per network and event.
- **Results visibility** is per event: live, after voting closes (the default), or organiser only.

**Accounts and privacy:** password reset by email, email confirmation (required before sending messages), optional two-step login with an authenticator app, logins that expire (7 days by default), and signing out other devices after a password change. Voter contact details are erased automatically after the event (30 days by default) or on demand. Privacy and Terms pages are included.

## Tech stack

| Layer        | Technology                                                                       |
| ------------ | -------------------------------------------------------------------------------- |
| API          | Laravel 13 (PHP 8.3+), Sanctum token auth, Pest tests                            |
| Database     | MySQL / MariaDB (SQLite works for local dev and tests)                           |
| Frontend     | Nuxt 4 (Vue 3, TypeScript) SPA, Pinia, Bootstrap 5 grid + custom design system   |
| Live results | Laravel Reverb (self-hosted websockets) + Laravel Echo. Optional: falls back to 15-second polling. |

## Project structure

```
ballotify/
├── backend/                 Laravel API
│   ├── app/Http/Controllers/Api/   Auth, Event, Category, Candidate, Voter, Vote
│   ├── app/Policies/EventPolicy.php  organisers can only touch their own events
│   ├── app/Events/VoteCast.php       live-results broadcast
│   ├── database/migrations/          schema (users, events, categories, candidates + vote totals, voters, audit_logs)
│   ├── database/seeders/             demo data (admin@demo.com / password)
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
php artisan serve --port=8883     # the API
php artisan reverb:start          # optional: instant live results (second terminal)
php artisan schedule:work         # optional: daily cleanup (third terminal)
```

Or run all three at once with `composer dev`.

No queue worker is needed. With the default `QUEUE_CONNECTION=sync`, invitations are sent right after you click **Send**. For very large lists you can switch to `QUEUE_CONNECTION=database` and run `composer dev`, which starts the API plus a background worker.

The demo login is `admin@demo.com` / `password`. It is also the platform admin (sidebar → **Admin**). To make another account an admin, run `php artisan ballotify:make-admin someone@example.com`. The demo voter codes are written to `storage/app/private/seed_voter_codes.json` (the first event's codes look like `E1-0150`).

> **Upgrading from an older copy?** The schema was consolidated, so rebuild the database with `php artisan migrate:fresh --seed`. This **deletes all existing data**.


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
| `BROADCAST_CONNECTION` | `reverb` for instant live results, `log` to disable. |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Reverb server settings. Use your own random key and secret. |
| `SANCTUM_EXPIRATION` | Minutes an organiser login stays valid (default 10080 = 7 days). |
| `VOTER_CONTACT_RETENTION_DAYS` | Days after an event closes before voter emails and phones are erased (default 30, `0` = never). Needs the scheduler. |

### Email & SMS invitations

Organisers can store each voter's **email** and **phone**. **Voters → Send codes** then delivers the voting link and personal code by email, SMS or both. Delivery runs in the background, and the result is shown per voter: *Invited* or *Not delivered*, with the reason.

The app works with no credentials. In that **test mode**, messages are written to `storage/logs/laravel.log` instead of being sent, and the Send dialog says so. To go live, fill in one of these in `backend/.env`:

| Channel | Settings |
|---|---|
| Email | `MAIL_MAILER=smtp` (or `mailgun`, `postmark`, `ses`, `resend`) plus that mailer's credentials. Set `MAIL_FROM_ADDRESS` to an address on your domain. |
| SMS: Termii | `SMS_DRIVER=termii`, `TERMII_API_KEY`, `TERMII_SENDER_ID` (approved sender ID). Optionally `TERMII_CHANNEL=dnd` to reach DND numbers in Nigeria. |
| SMS: Africa's Talking | `SMS_DRIVER=africastalking`, `AFRICASTALKING_USERNAME`, `AFRICASTALKING_API_KEY`, optional `AFRICASTALKING_FROM` |
| SMS: Twilio | `SMS_DRIVER=twilio`, `TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` |
| Phone format | `SMS_DEFAULT_COUNTRY_CODE=234` turns local numbers like `0801 234 5678` into `+2348012345678`. |

After editing `.env`, run `php artisan config:clear`. If your network blocks port 465 (common on home connections), use `MAIL_PORT=587` with `MAIL_SCHEME=smtp`.

A provider that fails never breaks the app:
- The error is saved on that voter and shown in the Voters tab.
- Everything else keeps working.
- People who have already voted are never messaged.

### Frontend `.env`

| Key                          | Purpose                                                                      |
| ---------------------------- | ---------------------------------------------------------------------------- |
| `NUXT_PUBLIC_API_BASE`       | API base URL, e.g. `https://api.example.com/api`                             |
| `NUXT_PUBLIC_REVERB_KEY` | Same as `REVERB_APP_KEY`. Leave empty to use 15-second polling. |
| `NUXT_PUBLIC_REVERB_HOST` / `_PORT` / `_SCHEME` | Where browsers reach Reverb, e.g. `ws.example.com` / `443` / `https`. |
| `NUXT_PUBLIC_COMPANY_NAME`, `NUXT_PUBLIC_CONTACT_EMAIL` | Shown on the Privacy and Terms pages. |

## Testing

```bash
cd backend  && php artisan test                                    # 66 feature tests
cd frontend && npx nuxi prepare && npx vue-tsc --noEmit -p .nuxt/tsconfig.app.json   # type check
cd frontend && npm run build                                       # production build
```

## Deploying

**API.** Deploy it like any Laravel app:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and the real `APP_URL` and `FRONTEND_URL`.
2. Run `composer install --no-dev -o`, `php artisan migrate --force` and `php artisan config:cache route:cache`.
3. Point the web root at `backend/public`, and make sure `public/candidates/` is writable for photos.
4. Add one cron entry for daily cleanup (expired logins, old voter contacts): `* * * * * cd /path/to/backend && php artisan schedule:run`.
5. For instant live results, keep `php artisan reverb:start` running (e.g. with Supervisor) behind your web server's websocket proxy, and set the `REVERB_*` / `NUXT_PUBLIC_REVERB_*` values to the public host. Without it, results refresh every 15 seconds.

**Frontend.** It's a static SPA:

1. Build with `NUXT_PUBLIC_API_BASE=https://your-api/api npx nuxi generate`.
2. Upload `.output/public` to any static host (Netlify, Vercel, S3, cPanel…).
3. Serve `index.html` for unknown routes (SPA fallback) so links like `/vote/<token>` work.

## API reference

See [backend/docs/API.md](backend/docs/API.md).

## License

Proprietary. All rights reserved.
