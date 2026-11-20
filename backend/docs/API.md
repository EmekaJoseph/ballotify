# Ballotify API reference

- **Base URL:** `{APP_URL}/api` (local default `http://localhost:8883/api`)
- **Format:** JSON. Send `Accept: application/json`.
- **Auth:** organiser routes need `Authorization: Bearer <token>`. Tokens expire after `SANCTUM_EXPIRATION` minutes (7 days by default).
- **Errors:**
  - `422` validation: `{ message, errors: { field: [msg] } }`
  - `401` not signed in, or login expired
  - `403` event not open / results not published / email not confirmed / account suspended (`{ message, suspended: true }`)
  - `404` not found, or not your event
  - `402` the event's plan doesn't allow it (Free limits): `{ message, plan_limit: 'events' | 'voters' | 'messaging', upgrade }`
  - `409` the ballot is locked (voting has started): `{ message, locked: true }`
  - `429` rate limited

## Authentication

| Method | Path | Body | Response |
|---|---|---|---|
| POST | `/auth/register` | `name, email, password (min 8), terms (must be true)` | `201 { token, user }`, and a confirmation email is sent |
| POST | `/auth/login` | `email, password` | `{ token, user }`, or `{ two_factor: true, challenge }` when two-step login is on |
| POST | `/auth/two-factor` | `challenge, code` (6-digit app code or a recovery code) | `{ token, user }` |
| POST | `/auth/forgot-password` | `email` | Always `{ message }` (doesn't reveal whether the account exists) |
| POST | `/auth/reset-password` | `token, email, password, password_confirmation` | `{ message }`, and every session is signed out |
| GET | `/auth/verify-email/{id}/{hash}?expires&signature` | Signed link from the confirmation email | `{ verified: true }` |
| POST | `/auth/email/resend` | Bearer token | `{ message }` |
| POST | `/auth/logout` | Bearer token | `{ message }` (revokes the token) |
| GET | `/me` | | `user`, including `email_verified_at` and `two_factor_enabled` |
| PATCH | `/me` | `name?, password?, current_password (required with password), payout_bank_name?, payout_account_number? (10 digits), payout_account_name?` | `user`. The email can't be changed. A new password signs out all other devices. |
| POST | `/me/onboarded` | | `user` with `onboarded_at` set (first-run tour seen) |
| POST | `/me/two-factor` | `password` | `{ secret, otpauth_url }` (not active until confirmed) |
| POST | `/me/two-factor/confirm` | `code` | `{ recovery_codes: string[8], user }` |
| DELETE | `/me/two-factor` | `password` | `{ user }` |

Sign-in, registration, two-factor and password-reset endpoints are limited to 10 requests per minute per IP. The emailed links open the frontend (`FRONTEND_URL/reset-password`, `FRONTEND_URL/verify-email`).

## Events (organiser)

| Method | Path | Notes |
|---|---|---|
| GET | `/event-templates` | `[ { key, name, summary, icon, voting_mode, results_visibility, vote_price_naira, categories: string[] } ]` |
| GET | `/events` | Events you own **or co-organise**, newest first, with counts, `my_role` and `abilities` |
| POST | `/events` | Create (body below). Drafts are unlimited on every plan. |
| GET | `/events/{id}` | Event with `categories` and `candidates` (voters are fetched separately) |
| PATCH | `/events/{id}` | Partial update (owner/editor). `voting_mode` can't change once the ballot is locked. |
| POST | `/events/{id}/branding` | Pro (else `402 branding`). Multipart `brand_color? (#RRGGBB), logo? (image, 2 MB), remove_logo?` |
| GET | `/events/{id}/earnings` | Paid voting: `{ votes_sold, payments, gross_naira, fee_naira, net_naira, paid_out_naira, balance_naira, fee_percent, late_payments, payouts, recent }` (buyer emails masked) |
| DELETE | `/events/{id}` | Owner only. Deletes the event with its voters, totals and activity log |
| GET | `/events/{id}/voting-link` | `{ token, public_url, results_url }` (frontend URLs) |
| GET | `/events/{id}/results` | The organiser's view of the tally, whatever the public visibility setting |
| GET | `/events/{id}/activity` | Activity log, newest first: `[ { action, description, during_voting, created_at, user } ]` |
| POST | `/events/{id}/duplicate` | `201` new event. Copies settings, categories (with pick limits) and candidates (with descriptions and photos). Voters, votes, dates and the activity log are not copied. |

**Event body:**
- `name` (string, required on create)
- `description` (string, nullable)
- `voting_mode`: `codes` (secret ballot, default) or `paid` (public paid voting)
- `vote_price_naira`: required for `paid` (10–100000)
- `template` (create only): `student_union`, `award_night`, `agm` or `board`; adds its categories and defaults
- `reminder_at`, `reminder_channel` (`email|sms|both`) (update only, Pro): a scheduled reminder to everyone who hasn't voted; must be in the future and before `ends_at`. `null` cancels it.
- `results_visibility`: `live`, `after_close` (default) or `private`
- `starts_at`, `ends_at` (ISO dates, nullable; `ends_at` must not be before `starts_at`)

**Event object adds:**
- `plan` (`free` or `pro`), `upgraded_via` (`payment`, `admin` or null)
- `limits`: `{ plan, max_voters, messaging, branding, custom_branding, team, max_members, via_account }`, the effective plan (an admin-granted Pro account makes every event Pro)
- `my_role` (`owner`, `editor`, `voters`, `viewer`) and `abilities` (`view`, `voters`, `manage`, `own`)
- `voting_mode`, `vote_price_naira`, `votes_sold` (paid), `brand_color`, `brand_logo_url`, `reminder_at`, `reminder_channel`, `reminder_sent_at`, `members_count`
- `status`: `upcoming`, `active` or `completed`, computed from the voting window
- `ballot_locked`: true once the first vote is cast or voting has closed
- `categories_count`, `candidates_count`, `voters_count`, `voted_count`

## Categories (organiser)

| Method | Path | Body |
|---|---|---|
| GET | `/events/{id}/categories` | |
| POST | `/events/{id}/categories` | `name` (unique per event), `max_choices?` (1 = choose one, the default; up to 1000; `null` = no limit), `shuffle_candidates?`. Returns `409` when the ballot is locked. |
| PATCH | `/events/{id}/categories/{categoryId}` | `name?`, `max_choices?`, `shuffle_candidates?`. Renaming and shuffling are allowed while locked (and logged); changing `max_choices` returns `409` when locked. |

`max_choices` only applies to `multiple` events: it's the most candidates a voter may pick in that category. Categories and candidates are always returned in the order they were added.
| DELETE | `/events/{id}/categories/{categoryId}` | Also deletes its candidates. Returns `409` when locked. |

## Candidates (organiser)

These endpoints take `multipart/form-data`, because they can include a photo.

| Method | Path | Body |
|---|---|---|
| GET | `/events/{id}/candidates` | |
| POST | `/events/{id}/candidates` | `category_id, name, description?` (max 280 chars), `image?` (jpg/png/webp/gif, max 5 MB). Returns `409` when locked. |
| POST or PATCH | `/events/{id}/candidates/{candidateId}` | `category_id?, name?, description?, image?, remove_image?`. Name, description and photo changes are allowed while locked (and logged). Send an empty `description` to clear it. Changing `category_id` returns `409` when locked. |
| DELETE | `/events/{id}/candidates/{candidateId}` | Also deletes the photo. Returns `409` when locked. |
| POST | `/events/{id}/candidates/import` | JSON `candidates: [ { category, name, description? } ]` (max 2000). Missing categories are created (matched case-insensitively). Names already in a category are skipped. Returns `201 { created, categories_created, skipped: [ { row, reason } ] }`, or `409` when locked. |

Names are unique within a category. Candidate objects include `image_url` (an absolute URL, or null). They never include vote totals; only the results endpoints return those. Use **POST** for updates that include a file, because PHP does not parse multipart bodies on PATCH.

## Voters / codes (organiser)

| Method | Path | Body | Response |
|---|---|---|---|
| GET | `/events/{id}/voters` | | `[ { id, name, email, phone, code, used_at, invited_at, invite_error } ]` |
| POST | `/events/{id}/voters` | `name?, email?, phone?, code?` (generated if omitted) | voter |
| POST | `/events/{id}/voters/generate` | `count (1–10000)` | `201 { voters: [ { name, email, phone, code } ] }` |
| POST | `/events/{id}/voters/import` | `voters: [ { name?, email?, phone? } ]` (max 10000; legacy `names: string[]` also accepted) | `201 { voters: [...] }` |
| PATCH | `/events/{id}/voters/{voterId}` | `name?, email?, phone?, code?` | voter. Sending `used_at` returns `422`, because votes are secret and a used code can't be reset. |
| DELETE | `/events/{id}/voters/{voterId}` | | `204`. If they already voted, the vote stays counted. |
| POST | `/events/{id}/voters/erase-contacts` | | `{ erased }`: removes every voter's email and phone |
| POST | `/events/{id}/voters/invite` | `channel: email\|sms\|both, audience: not_invited\|pending\|selected, voter_ids?` | `{ queued, email, sms, failed, queue }`. Requires a confirmed email, otherwise `403`. |
| POST | `/events/{id}/voters/remind` | `channel: email\|sms\|both` | Reminder to everyone who hasn't voted (Pro, voting open). Same response as invite. |
| POST | `/events/{id}/voters/{voterId}/shared` | `channel: whatsapp` | voter: marks the code as shared by the organiser (`invited_via: whatsapp`) |

Voter endpoints return `422` for paid-voting events (they have no codes). Adding the first voters to a Free event makes it **live**; if the account already has its maximum live Free events, this returns `402 { plan_limit: "events" }`.

## Voting options, nominations, profiles, certificates (organiser)

| Method | Path | Notes |
|---|---|---|
| PATCH | `/events/{id}` | Also takes `self_registration`, `member_id_label` (Pro), `ussd_enabled` (Pro; creates a 6-digit `ussd_code`), `nominations_enabled`, `nominations_ends_at` (before `starts_at`). |
| POST | `/events/{id}/voters/numeric-codes` | USSD: replace the codes of voters who haven't voted with 10-digit numbers: `{ replaced }` |
| GET | `/events/{id}/nominations` | `{ open, enabled, ends_at, total, categories: [ { id, name, nominees: [ { name, count, reasons, on_ballot } ] } ] }` |
| POST | `/events/{id}/nominations/promote` | `{ category_id, name }`: add a nominee to the ballot (`409` once locked) |
| GET / DELETE | `/events/{id}/candidates/{candidateId}/profile-link` | Get (or create) the candidate's private link `{ url }`; DELETE switches it off |
| GET / POST | `/events/{id}/certificates` | List certificates, or certify the final results (`422` until voting closes; same totals return the existing certificate) |

Voters also take an optional `member_id` (unique per event, matched case-insensitively) on add, edit and import. On USSD events, typed codes must be numbers only.

## Account, payouts and data rights

| Method | Path | Notes |
|---|---|---|
| GET | `/billing/banks` | `{ banks: [ { name, code } ], verified }` (`verified` = Paystack is set up) |
| POST | `/billing/resolve-account` | `{ bank_code, account_number }` returns `{ account_name }` |
| PUT | `/me/payout-account` | `{ bank_code, account_number }`: verifies the account and creates or updates a Paystack subaccount; paid votes are then split automatically |
| GET | `/me/export` | Everything held about the account, as JSON |
| DELETE | `/me` | `{ password }`. Deletes the account and its events. `422` while paid-voting money is still owed to the organiser. |

## Team (co-organisers)

| Method | Path | Body | Returns |
|---|---|---|---|
| GET | `/events/{id}/members` | | `{ owner: { name, email }, members: [ { id, email, name, role, joined } ] }` |
| POST | `/events/{id}/members` | `email, role: editor\|voters\|viewer` | Owner only, Pro events only (`402 team`), up to 10. Emails an invitation; `joined` once that person has a confirmed account with that email. |
| PATCH | `/events/{id}/members/{memberId}` | `role` | Owner only |
| DELETE | `/events/{id}/members/{memberId}` | | Owner removes someone, or a member leaves |

Roles: **editor** (ballot, settings, voters, results; not delete or team), **voters** (voter list, codes, invitations, results), **viewer** (read only). People with no role get `404`; a role that doesn't allow an action gets `403`.
| GET | `/messaging/status` | | `{ email: { live, provider }, sms: { live, provider }, queue }` |

How contacts are handled:
- **Emails** are lower-cased.
- **Phones** are normalised to international format (`+234…`). Local numbers are converted using `SMS_DEFAULT_COUNTRY_CODE`.
- **Import errors** name the exact row, e.g. `voters.3.email`.
- **Retention:** contacts are erased automatically `VOTER_CONTACT_RETENTION_DAYS` days after voting closes (default 30). This runs from the daily scheduler (`ballotify:purge-voter-contacts`).

How invitations are sent:
- With `QUEUE_CONNECTION=sync` (the default), messages go out right after the response. With a database or redis queue they are sent by a worker.
- Voters who have voted are skipped, and so are voters with no contact on the chosen channel.
- Invitations can't be sent after voting has closed.
- **Success** sets `invited_at`.
- **Failure** saves the reason in `invite_error` and never raises an error to the caller.

How codes behave:
- Codes are stored and matched **upper-case**, so voters can type them in any case.
- Generated codes have 8 characters with no look-alikes (0/O, 1/I/L).

## Public (voters)

| Method | Path | Notes |
|---|---|---|
| GET | `/public/events/{token}` | Ballot of an **open** event: `{ id, name, description, status, voting_mode, vote_price_naira, payments_enabled, min_payment_naira, results_visibility, branding, brand, self_registration, member_id_label, starts_at, ends_at, categories, candidates }`. Shuffled categories come back in a new random order each time. |
| POST | `/public/events/{token}/request-code` | Self-registration: `{ member_id, captcha_token? }`. Sends the voter's code to the email/phone the organiser has on file: `{ message, sent_to: ['ad••@gmail.com'] }`. `404` ID not on the list, `409` already voted, `422` no contact on file, `429` asked in the last 2 minutes, `403` not switched on. |
| GET / POST | `/public/events/{token}/nominations` | GET: the nomination form `{ name, open, ends_at, voting_starts_at, brand, categories }`. POST: `{ category_id, nominee_name, reason?, email?, captcha_token? }` (max 3 per person per category). |
| GET / POST | `/public/candidate-profile/{token}` | The candidate's private link. POST (multipart): `description?, image?, remove_image?`. Name and category can't change. |
| GET | `/public/certificates/{serial}` | Verify a results certificate: `{ serial, payload, signature, valid, matches_current, message, verify_url, certificate_url }`. |
| POST | `/public/events/{token}/code/validate` | `{ code }` returns `{ valid: true, name }`, or `422` if invalid/used, or `403` if not open |
| POST | `/public/events/{token}/vote` | `{ code, choices: [ { category_id, candidate_id } ] }` returns `{ message, receipt }` |
| POST | `/public/events/{token}/receipt` | `{ receipt }` (ballot receipt, or a paid-vote reference) returns `{ valid, message, counted_at }` or `404` |
| POST | `/public/events/{token}/paid-votes` | `{ candidate_id, quantity (1–10000), email, name?, captcha_token? }` returns `{ authorization_url, reference }` (Paystack). `503` if payments aren't set up; `422` below the minimum payment. |
| POST | `/public/paid-votes/verify` | `{ reference }` returns `{ status: success\|pending\|failed\|late, message, quantity, candidate, reference }` |
| GET | `/public/events/{token}/results` | `{ event, stats, integrity: { changes_during_voting }, timeline: { bucket_minutes, points: [ { t, votes } ] }, categories: [ { id, name, candidates: [ { id, name, description, image_url, votes } ], leader } ] }`, or `403 { message, visibility, event }` while results aren't published |

**Not open.** For an event that hasn't started or has ended, `GET /public/events/{token}` returns `403 { message, status, starts_at, ends_at, results_public, brand, nominations_open }`.

**Bot check.** When `TURNSTILE_SECRET_KEY` is set, sign-up, paid-vote checkout, nominations and self-registration need a `captcha_token` from the Cloudflare Turnstile widget; otherwise they return `422 { errors: { captcha_token } }`.

**USSD (Africa's Talking).** `POST /ussd/{USSD_WEBHOOK_SECRET}` receives `sessionId, serviceCode, phoneNumber, text` and answers `text/plain` starting with `CON` (more input) or `END`. Menu: event number, voting code, one screen per category (`0` skip, `98` more), confirm. Ballots go through the same checks as `/vote`.

**How a ballot is processed:** the server checks each of these before recording anything.
- The event is inside its voting window.
- Every candidate belongs to the event and to the stated category.
- No more picks in a category than its `max_choices` (1 = choose one; null = no limit).
- The code exists and is unused. The check runs under a row lock in the same transaction.

**What gets stored (secret ballot):** each chosen candidate's total goes up by one, and the code is marked as used. No ballot row, choice, timestamp or IP is stored that could link the vote to the voter.

**Rate limits:** code validation and voting allow 30 requests per minute per IP per event. Public reads allow 120 per minute per IP.

## Plans and payments (Paystack)

Pay per event. Free events: a limited number of open events per account, a voter cap, no email/SMS, and "Powered by Ballotify" on the ballot. Pro removes those for one event.

| Method | Path | Notes |
|---|---|---|
| GET | `/public/plans` | Public pricing: `{ billing_enabled, payments_enabled, currency, pro_price_naira, free: {...}, pro: {...} }` |
| GET | `/billing/plans` | The same, plus `account_pro` for the signed-in organiser |
| POST | `/events/{id}/upgrade` | Starts a Paystack checkout: `{ authorization_url, reference }`. Returns `409` if already Pro, `422` if voting has closed, and `503 { payments_enabled: false }` if no Paystack key is set. |
| POST | `/billing/verify` | `{ reference }` (the organiser's own payment). Asks Paystack, then upgrades the event: `{ status: success\|pending\|failed, message, event_id }`. A receipt is emailed. |
| GET | `/billing/payments` | The organiser's own payments |
| GET | `/billing/payments/{reference}` | One successful payment, for the printable receipt |
| POST | `/paystack/webhook` | Paystack server-to-server notification. It must carry a valid `x-paystack-signature` (HMAC-SHA512), otherwise it gets `401`. The transaction is always re-checked with Paystack. |

Payments are settled exactly once, under a row lock. An event is upgraded only when Paystack reports `success` for at least the stored amount in the stored currency. An abandoned checkout stays pending, so the organiser can still finish paying.

Limits enforced (`402`):
- Adding the first voters to a Free event when the account already has its maximum live Free events (`events`). Drafts are never limited.
- Adding, generating or importing voters beyond the event's cap.
- Sending email/SMS invitations or reminders from a Free event (`messaging`), custom branding (`branding`), co-organisers (`team`).

**Paid public voting** uses the same Paystack account and webhook (references start with `PV-`). A purchase is counted once, under a row lock, only for the full amount, and only if Paystack's `paid_at` is before `ends_at` (otherwise `late`: not counted, refund due). The platform fee is `paid_vote_fee_percent` (Admin → Pricing).

## Platform admin

For accounts with `is_admin` (grant it with `php artisan ballotify:make-admin you@example.com`, remove it with `--revoke`). Everyone else gets `404`. These endpoints never return ballot contents, voter lists or codes.

| Method | Path | Notes |
|---|---|---|
| GET | `/admin/overview` | `{ users, users_last_30_days, suspended_users, events, events_live, voters, ballots_cast, votes_counted, pro_events, revenue_naira, revenue_last_30_days_naira, paid_votes_gross_naira, paid_votes_fees_naira }` |
| GET | `/admin/users?search&filter&page` | `filter`: `suspended`, `unverified` or `admins`. Paginated (25), with `events_count`. |
| POST | `/admin/users/{id}/suspend` | Signs the user out everywhere and blocks sign-in. Their voting links and results pages return `404` until restored. You can't suspend yourself or another admin. |
| POST | `/admin/users/{id}/unsuspend` | Restores the account. |
| GET | `/admin/events?search&status&page` | Every event with its organiser, plan and turnout. `status`: `upcoming`, `active` or `completed`. Paginated (25). |
| POST | `/admin/events/{id}/plan` | `{ plan: free\|pro }`: upgrade or downgrade an event without payment (logged in its activity) |
| POST | `/admin/users/{id}/pro` | `{ enabled }`: make every event of the account Pro, free of charge |
| GET / PUT | `/admin/plans` | Read or change `pro_price_naira`, `pro_max_voters`, `free_max_voters`, `free_max_active_events` (live events), `paid_vote_fee_percent` |
| GET | `/admin/payouts` | Paid-voting events with organiser bank details and `gross / fee / net / paid_out / balance` |
| POST | `/admin/events/{id}/payouts` | `{ amount_naira, note? }`: record money sent to the organiser |
| GET | `/admin/health` | Queue driver, pending and failed jobs, scheduler heartbeat, mail/SMS drivers, Paystack, recent delivery errors |
| POST | `/admin/health/failed-jobs/{uuid\|all}/retry` | Retry failed jobs |
| DELETE | `/admin/health/failed-jobs/{uuid\|all}` | Remove failed jobs |
| GET | `/admin/payments?search&status&page` | Paystack payments with organiser and event. `status`: `pending`, `success` or `failed`. |

## Live results (Laravel Reverb)

When `BROADCAST_CONNECTION=reverb` and results are public, each accepted ballot broadcasts:
- channel: `event.{eventId}` (public)
- event: `vote.cast`
- payload: `{ event_id }` (no ballot content)

The results page refetches the totals when it receives one. Without Reverb it refreshes every 15 seconds.
