<p align="center">
  <img src="frontend/public/logo.svg" width="88" alt="Ballotify logo" />
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
| Start from a template (church / ministry, AGM / association, board, award night, school / student union) or a blank ballot | Enter a personal one-time code         |
| Per category: choose one, pick up to N, **approval** (any number) or **ranked choice** (instant runoff); shuffle candidates |
| **Motions** (Yes / No / Abstain) with simple, two-thirds or three-quarters majority |
| **Voter groups**: categories only some voters see (e.g. a branch rep only that branch votes for) |
| **Campaign cards**: a shareable image per candidate with a QR code to the ballot | Progress bar, review screen, then submit |
| Schedule when voting opens and closes, or close it early           | Pick candidates, review, submit        |
| Generate thousands of voter codes, or import a name list           | A **receipt** to check the ballot was counted |
| Export codes to CSV, or print voter slips with a QR code           |                                        |
| Email, text or **WhatsApp** codes to voters; remind non-voters (now or scheduled) | Results page (when the organiser publishes it) |
| Choose when results are public: live, after close, or never        |                                        |
| Import candidates from a spreadsheet; duplicate an event            |                                        |
| Results as CSV, a printable report, a turnout chart, and **Present / Reveal** on a projector | **Paid voting:** pick a nominee, pay per vote |
| **Paid public voting** for award nights: fans pay per vote via Paystack (Flutterwave as automatic backup) | Payment reference doubles as a receipt |
| **Co-organisers** with roles (editor, voter manager, viewer)       | **Self-registration:** request your code with your membership number / ID |
| Optional: voter self-registration, USSD voting, public nominations, candidate profile links | **USSD voting** on any phone, no data needed |
| **Certified results** with a QR code anyone can verify             | Check a results certificate at /verify |
| Automatic payouts for paid voting (Paystack split payments)        |                                        |
| Pro: your own logo and colour on the ballot and results pages      |                                        |
| Activity log of every change; two-step login (Google Authenticator); dark mode |
| Voting pages in **English, Yorùbá, Hausa, Igbo and French**         |                                        |
| Idle ballots warn after 1 minute without a tap, then close 30 s later |                                        |                                        |
| **Platform admin:** organisers, events, payments, payouts, system health; suspend abuse |                                        |
| **Free and Pro plans:** pay per event with Paystack                 |                                        |

**Integrity guarantees, all enforced on the server:**

- **Secret ballot.** Only a running total per candidate is stored, with no record of who voted for whom: no ballot rows, timestamps or IPs. Not even the organiser or a database administrator can link a vote to a voter.
- Each code can vote **exactly once**. The voter row is locked inside a transaction, so two simultaneous submits can't both succeed. A code that has voted can't be reset.
- **The ballot locks at the first vote.** Categories and candidates can't be added, removed or moved. Name and photo fixes are still allowed, but each is recorded in the event's activity log, and the public results page shows how many changes were made during voting.
- Ballots outside the voting window are rejected.
- Pick limits are checked server side per category, and candidates must belong to the category and event.
- Ranked choice is counted in rounds (instant runoff). The rankings are kept to count them, with no voter id, no time and random ids, so they can't be traced to anyone.
- **Ballot receipts.** Each counted ballot gets a receipt code. Only a keyed hash is stored, with no link to the voter or the choices, so a voter can prove their ballot was counted without revealing how they voted.
- Code guessing is rate-limited per network and event.
- **Results visibility** is per event: live, after voting closes (the default), or organiser only.

**Accounts and privacy:** password reset by email, email confirmation (required before sending messages), optional two-step login with Google Authenticator (codes can't be reused; 5 wrong codes lock the step for 15 minutes; recovery codes can be renewed), logins that expire (7 days by default), and signing out other devices after a password change. Voter contact details are erased automatically after the event (30 days by default) or on demand. Privacy and Terms pages are included.

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
2. Open **Annual Elections 2026**, go to the **Share** tab and open the voting link.
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

### Plans and payments (Paystack)

Billing is **pay per event**:

| | Free | Pro (one-off, per event) |
|---|---|---|
| Events | Unlimited drafts; **1 live** at a time (an event goes live when it gets its first voters) | Doesn't count towards the free limit |
| Voters per event | 50 | 5,000 |
| Email & SMS invitations and reminders | No (share codes on WhatsApp, CSV or printed slips) | Yes |
| Ballot branding | "Powered by Ballotify" | None, plus your own logo and colour |
| Co-organisers | No | Up to 10 |
| Price | ₦0 | ₦10,000 |

These are the starting values. Change prices and limits any time in **Admin → Pricing**; changes apply straight away.

Organisers upgrade from the event page. They pay on Paystack's checkout (card, bank transfer or USSD) and are sent back to the event, which is upgraded once Paystack confirms the payment. A webhook covers organisers who close the tab before returning.

Every successful payment is emailed as a receipt, and organisers can print receipts from **Account → Payments & receipts**.

**Paid public voting** (award nights, pageants) is separate from the plans: anyone with the link picks a nominee and pays per vote. Votes are added once Paystack confirms, exactly once, and only if the payment arrived before voting closed (late payments are marked for refund). The platform keeps a fee (10% by default, **Admin → Pricing**); the rest is the organiser's. Organisers add their bank details on the event's **Earnings** tab; you send their share by transfer and record it in **Admin → Payouts**. Paid-voting events never count towards the free live-event limit.

The platform admin can also upgrade without payment:
- **Admin → Events → Make Pro** upgrades one event.
- **Admin → Organisers → Give Pro** makes every event of an account Pro, for partners, sponsors or staff.
- Admin accounts are always Pro.

To go live:
1. In `backend/.env`, set `PAYSTACK_SECRET_KEY` (a test key `sk_test_…` works for trying it out). Then run `php artisan config:clear`.
2. In the Paystack dashboard (Settings → API Keys & Webhooks), set the webhook URL to `{APP_URL}/api/paystack/webhook`.
3. Optional backup: set `FLUTTERWAVE_SECRET_KEY` and `FLUTTERWAVE_WEBHOOK_HASH` (any long random string). In Flutterwave (Settings → Webhooks) set the URL to `{APP_URL}/api/flutterwave/webhook` and the same secret hash. If Paystack can't start a checkout, payers are sent to Flutterwave automatically, and Paystack is skipped for two minutes. Admin → Health shows when that happens.

Until a key is set, nothing breaks: organisers see "Contact us to upgrade" (using `NUXT_PUBLIC_CONTACT_EMAIL`), and you can upgrade events from the admin page. Set `BILLING_ENABLED=false` to remove plans entirely, for example for a private installation; every event then behaves as Pro.

| Key | Purpose |
|---|---|
| `BILLING_ENABLED` | `true` (default) for Free/Pro plans, `false` for no limits and no payments. |
| `PAYSTACK_SECRET_KEY` | Your Paystack secret key. Empty = online payment off (unless Flutterwave is set). |
| `FLUTTERWAVE_SECRET_KEY` | Backup payment provider. Empty = no backup. |
| `FLUTTERWAVE_WEBHOOK_HASH` | The secret hash you also enter in Flutterwave's webhook settings. |
| `PAYMENT_PROVIDERS` | Order of preference, default `paystack,flutterwave`. |
| `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile secret (free bot / card-testing check on sign-up, paid votes, nominations, self-registration). Empty = off. Put the matching site key in the frontend as `NUXT_PUBLIC_TURNSTILE_SITE_KEY`. |
| `USSD_SERVICE_CODE` | Your Africa's Talking USSD code, e.g. `*384*1234#` (shown to organisers). Empty = USSD voting unavailable. |
| `USSD_WEBHOOK_SECRET` | A long random string. In Africa's Talking set the USSD callback URL to `{APP_URL}/api/ussd/{USSD_WEBHOOK_SECRET}`. |

**Automatic payouts:** with Paystack set up, organisers verify their bank account on the event's Earnings tab and Ballotify creates a Paystack subaccount for them. Every paid vote is then split by Paystack: the platform fee to you, the rest straight to the organiser. Payments that went through the Flutterwave backup aren't split: they show as owed on the Earnings tab. Without automatic payouts, record transfers in Admin → Payouts.

**Languages:** voting, results and nomination pages come in English, Yorùbá, Hausa, Igbo and French. English by default. Organisers can pick another default per event (Settings → Voting options), and voters can switch with the language button (their choice is remembered). Texts: `frontend/app/i18n/messages.ts`; API messages: `backend/lang/*.json`. Have a native speaker review the Yorùbá, Hausa and Igbo before a big event.

### Frontend `.env`

| Key                          | Purpose                                                                      |
| ---------------------------- | ---------------------------------------------------------------------------- |
| `NUXT_PUBLIC_API_BASE`       | API base URL, e.g. `https://api.example.com/api`                             |
| `NUXT_PUBLIC_REVERB_KEY` | Same as `REVERB_APP_KEY`. Leave empty to use 15-second polling. |
| `NUXT_PUBLIC_REVERB_HOST` / `_PORT` / `_SCHEME` | Where browsers reach Reverb, e.g. `ws.example.com` / `443` / `https`. |
| `NUXT_PUBLIC_COMPANY_NAME`, `NUXT_PUBLIC_CONTACT_EMAIL` | Shown on the Privacy and Terms pages. |
| `NUXT_PUBLIC_TURNSTILE_SITE_KEY` | Cloudflare Turnstile site key. Empty = no bot check shown. |

## Testing

```bash
cd backend  && php artisan test                                    # 133 feature tests
cd frontend && npx nuxi prepare && npx vue-tsc --noEmit -p .nuxt/tsconfig.app.json   # type check
cd frontend && npm run build                                       # production build
```

## Deploying

**API.** Deploy it like any Laravel app:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and the real `APP_URL` and `FRONTEND_URL`.
2. Run `composer install --no-dev -o`, `php artisan migrate --force` and `php artisan config:cache route:cache`.
3. Point the web root at `backend/public`, and make sure `public/candidates/` and `public/branding/` are writable (photos and logos).
4. Add one cron entry: `* * * * * cd /path/to/backend && php artisan schedule:run`. It sends scheduled reminders (every minute) and does daily cleanup (expired logins, old voter contacts). **Admin → Health** shows whether it is running.
5. For payments, set `PAYSTACK_SECRET_KEY` (live key) and the Paystack webhook URL `https://your-api/api/paystack/webhook`.
6. For instant live results, keep `php artisan reverb:start` running (e.g. with Supervisor) behind your web server's websocket proxy, and set the `REVERB_*` / `NUXT_PUBLIC_REVERB_*` values to the public host. Without it, results refresh every 15 seconds.

**Frontend.** It's a static SPA:

1. Build with `NUXT_PUBLIC_API_BASE=https://your-api/api npx nuxi generate`.
2. Upload `.output/public` to any static host (Netlify, Vercel, S3, cPanel…).
3. Serve `index.html` for unknown routes (SPA fallback) so links like `/vote/<token>` work.

## API reference

See [backend/docs/API.md](backend/docs/API.md).

## License

Proprietary. All rights reserved.
