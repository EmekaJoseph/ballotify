# E-voting launch checklist

Tick each item as it's done.

## Backend (API host)
- [ ] Upload `backend/` and point the web root at `backend/public`
- [ ] Create the MySQL database and set `DB_*` in `.env`
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<api-host>`
- [ ] `FRONTEND_URL=https://evoting.agudatechy.com`
- [ ] SMTP mail settings (`MAIL_*`), and send yourself a test email
- [ ] `TRUSTED_PROXIES=*` if behind Cloudflare or a proxy
- [ ] `php artisan key:generate` (first time only)
- [ ] `php artisan migrate --force --seed`
- [ ] `php artisan config:cache` and `php artisan route:cache`
- [ ] Cron: `* * * * * cd /path/to/backend && php artisan schedule:run`
- [ ] Optional: Pusher keys for live results (`BROADCAST_CONNECTION=pusher`)
- [ ] Optional: Cloudflare Turnstile keys (bot check)

## Frontend (evoting.agudatechy.com)
- [ ] `NUXT_PUBLIC_API_BASE=https://<api-host>/api`
- [ ] `NUXT_PUBLIC_SUPPORT_WHATSAPP`, `NUXT_PUBLIC_CONTACT_EMAIL`
- [ ] Optional: `NUXT_PUBLIC_PUSHER_KEY` / `NUXT_PUBLIC_PUSHER_CLUSTER`
- [ ] `npm run generate` and upload `.output/public/`
- [ ] SPA fallback: unknown paths serve `index.html`
- [ ] HTTPS certificate on `evoting.agudatechy.com`

## After going live
- [ ] Sign in as admin@agudatechy.com and **change the password**
- [ ] Turn on two-step login for the admin account
- [ ] Sign up a test organiser, approve it from **Admin**, run a small test election end to end
- [ ] Check **Admin → Health** shows the scheduler running
