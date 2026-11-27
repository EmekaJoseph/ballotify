<p align="center">
  <img src="frontend/public/brand/logo.png" width="110" alt="Agudatechy logo" />
</p>

<h1 align="center">E-voting by Agudatechy</h1>

<p align="center">Secret ballot elections online: one-time voter codes, a clean ballot on any device, and certified results.</p>

---

## What it does

- **Secret ballot only.** Every voter gets a one-time code. The system records that a code was used, never how anyone voted.
- **Approved organisers only.** Anyone can sign up, but they can't create events until a platform admin approves them (**Dashboard → Admin**).
- Categories are "choose one" or "pick up to N". No plans, no limits, no payments.
- Send codes by **email**, or share them on **WhatsApp** from the organiser's phone. Remind voters who haven't voted.
- **Nominations** before voting, **co-organisers** with roles, **imports** (Excel/CSV) and exports.
- Results page, printable report, **PDF ballot**, and signed **results certificates** anyone can verify at `/verify`.

## Project layout

| Folder      | What it is                                   | Hosted at                          |
| ----------- | -------------------------------------------- | ---------------------------------- |
| `backend/`  | Laravel 13 API (Sanctum tokens)              | your API host (any domain)         |
| `frontend/` | Nuxt 4 single-page app (`npm run generate`)  | `https://evoting.agudatechy.com`   |

## Run it locally

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed       # creates the tables and the admin account
php artisan serve --port=8890    # pick a port that's free (Ballotify uses 8883)
```

```bash
cd frontend
npm install
cp .env.example .env             # set NUXT_PUBLIC_API_BASE=http://localhost:8890/api
npm run dev -- --port 3001
```

The seeded platform admin is **admin@agudatechy.com** / **password123#**. Change that password straight after the first sign-in on a live server.

## Approving organisers

1. Someone signs up and confirms their email. Their dashboard says they're waiting for approval.
2. Sign in as the admin, open **Admin**. The **Accounts** tab opens on "Waiting for approval".
3. Click **Approve**. They can now create events. **Withdraw approval** stops new events; **Suspend** signs them out completely.

## Deploying

**Backend** (API host):

1. Upload `backend/`, point the web root at `backend/public`.
2. In `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<api-host>`, `FRONTEND_URL=https://evoting.agudatechy.com`, your MySQL details, and SMTP mail settings.
3. If the host sits behind a proxy or Cloudflare, set `TRUSTED_PROXIES=*`.
4. Run `composer install --no-dev -o`, `php artisan migrate --force --seed`, `php artisan config:cache`, `php artisan route:cache`.
5. Add one cron entry: `* * * * * cd /path/to/backend && php artisan schedule:run` (scheduled reminders and daily cleanup).
6. Optional live results: set `BROADCAST_CONNECTION=pusher` and the `PUSHER_*` values. Without it, results refresh every 15 seconds.

**Frontend** (`evoting.agudatechy.com`):

1. In `frontend/.env`: `NUXT_PUBLIC_API_BASE=https://<api-host>/api` (with `https://`), plus the Pusher key/cluster if used.
2. `npm run generate`, then upload `frontend/.output/public/`.
3. Make unknown paths serve `index.html` (SPA fallback), so links like `/vote/abc` work on refresh.

## Tests

```bash
cd backend  && php artisan test
cd frontend && npx nuxi typecheck
```
