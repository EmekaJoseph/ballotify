# Ballotify launch checklist

What's left before selling. See it any time with:

    cd backend && php artisan ballotify:launch

Tick an item by changing `[ ]` to `[x]` (or ask Claude to). Items marked
"(auto)" are also checked by the command against the real setup, so a tick
there is only a note: the command reports what it actually finds.

## Before taking money

- [ ] Set the new prices in Admin → Pricing (₦20,000 / ₦45,000 / ₦105,000, SMS ₦10) (auto)
- [ ] Run `php artisan migrate` on the server (auto)
- [ ] Browser click-through on a test event: meeting rules (weight, proxy, quorum), delete confirmations, duplicate-voter warnings, plan names, one-line Add forms for candidates and voters (Enter adds to the list; duplicates refused), nomination suggestions (pick or type), Excel import (voters, candidates, suggested names), ballot PDF export, bulk delete of ticked voters, reset votes after a test run (then check the results page, PDF report and certificate mention it)
- [ ] One real Paystack payment with a real card: Pro Small upgrade, event upgrades, receipt email arrives
- [ ] Buy a small SMS credit pack with a real card
- [ ] Real SMS invitation to your own phone: arrives, code works
- [ ] Real email invitation to your own inbox: arrives (not in spam), code works

## Production setup

- [ ] API `.env`: `FRONTEND_URL=https://ballotifyng.vercel.app` (links in emails), then `php artisan config:cache`. API runs at `https://ballotify.api.agudatechy.com`; CORS already allows any site
- [ ] Frontend built with `NUXT_PUBLIC_API_BASE=https://ballotify.agudatechy.com/api` (these are baked in at build time: rebuild after changing them), and the host sends every unknown path to `index.html` (single-page app)
- [ ] Paystack webhook: `https://ballotify.agudatechy.com/api/paystack/webhook`; Turnstile site key allowed on the frontend domain
- [ ] Live results via Pusher: API `.env` `BROADCAST_CONNECTION=pusher` + `PUSHER_APP_ID/KEY/SECRET/CLUSTER`; Vercel `NUXT_PUBLIC_PUSHER_KEY` + `NUXT_PUBLIC_PUSHER_CLUSTER` (empty `NUXT_PUBLIC_REVERB_KEY`), then redeploy
- [ ] `APP_DEBUG=false` (auto)
- [ ] `TRUSTED_PROXIES` set: `127.0.0.1` for Nginx on the same server, `*` behind Cloudflare (auto). Without it, one person's wrong codes block everyone.
- [ ] Update Symfony packages for the 2026-11-25 security advisories (`composer update "symfony/*"`, then run the tests)
- [ ] HTTPS on both the site and the API (auto)
- [ ] Live Paystack key (`sk_live_…`) and webhook URL set in the Paystack dashboard (auto: key)
- [ ] Real mail sender (not `log`) (auto)
- [ ] Real SMS provider (not `log`), with `SMS_DEFAULT_COUNTRY_CODE=234` (auto)
- [ ] Queue worker running (`php artisan queue:work`, kept alive by Supervisor or similar) (auto: queue driver, stuck and failed jobs)
- [ ] Scheduler cron: `* * * * * cd /path/to/backend && php artisan schedule:run` (auto)
- [ ] Daily database backups, and one restore tried
- [ ] Frontend `.env` on the server: `NUXT_PUBLIC_SUPPORT_WHATSAPP=+2348139590011` (WhatsApp chat button)

## Before selling widely

- [ ] One or two pilot events with a friendly church or association
- [ ] Native speaker checks the Yoruba, Igbo, Hausa and Pidgin translations
- [ ] Lawyer reviews the privacy policy and terms (NDPA)
