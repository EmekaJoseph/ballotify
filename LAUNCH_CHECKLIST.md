# Ballotify launch checklist

What's left before selling. See it any time with:

    cd backend && php artisan ballotify:launch

Tick an item by changing `[ ]` to `[x]` (or ask Claude to). Items marked
"(auto)" are also checked by the command against the real setup, so a tick
there is only a note: the command reports what it actually finds.

## Before taking money

- [ ] Set the new prices in Admin → Pricing (₦20,000 / ₦45,000 / ₦105,000, SMS ₦10) (auto)
- [ ] Run `php artisan migrate` on the server (auto)
- [ ] Browser click-through on a test event: meeting rules (weight, proxy, quorum), delete confirmations, duplicate-voter warnings, plan names
- [ ] One real Paystack payment with a real card: Pro Small upgrade, event upgrades, receipt email arrives
- [ ] Buy a small SMS credit pack with a real card
- [ ] Real SMS invitation to your own phone: arrives, code works
- [ ] Real email invitation to your own inbox: arrives (not in spam), code works

## Production setup

- [ ] `APP_DEBUG=false` (auto)
- [ ] HTTPS on both the site and the API (auto)
- [ ] Live Paystack key (`sk_live_…`) and webhook URL set in the Paystack dashboard (auto: key)
- [ ] Real mail sender (not `log`) (auto)
- [ ] Real SMS provider (not `log`), with `SMS_DEFAULT_COUNTRY_CODE=234` (auto)
- [ ] Queue worker running (`php artisan queue:work`, kept alive by Supervisor or similar) (auto: queue driver, stuck and failed jobs)
- [ ] Scheduler cron: `* * * * * cd /path/to/backend && php artisan schedule:run` (auto)
- [ ] Daily database backups, and one restore tried

## Before selling widely

- [ ] One or two pilot events with a friendly church or association
- [ ] Native speaker checks the Yoruba, Igbo, Hausa and Pidgin translations
- [ ] Lawyer reviews the privacy policy and terms (NDPA)
