# Ballotify API reference

- **Base URL:** `{APP_URL}/api` (local default `http://localhost:8883/api`)
- **Format:** JSON. Send `Accept: application/json`.
- **Auth:** organiser routes need `Authorization: Bearer <token>`. Tokens expire after `SANCTUM_EXPIRATION` minutes (7 days by default).
- **Errors:**
  - `422` validation: `{ message, errors: { field: [msg] } }`
  - `401` not signed in, or login expired
  - `403` event not open / results not published / email not confirmed
  - `404` not found, or not your event
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
| PATCH | `/me` | `name?, password?, current_password (required with password)` | `user`. The email can't be changed. A new password signs out all other devices. |
| POST | `/me/two-factor` | `password` | `{ secret, otpauth_url }` (not active until confirmed) |
| POST | `/me/two-factor/confirm` | `code` | `{ recovery_codes: string[8], user }` |
| DELETE | `/me/two-factor` | `password` | `{ user }` |

Sign-in, registration, two-factor and password-reset endpoints are limited to 10 requests per minute per IP. The emailed links open the frontend (`FRONTEND_URL/reset-password`, `FRONTEND_URL/verify-email`).

## Events (organiser)

| Method | Path | Notes |
|---|---|---|
| GET | `/events` | Your events, newest first, with counts |
| POST | `/events` | Create (body below) |
| GET | `/events/{id}` | Event with `categories` and `candidates` (voters are fetched separately) |
| PATCH | `/events/{id}` | Partial update. `selection_mode` can't change once the ballot is locked. |
| DELETE | `/events/{id}` | Deletes the event with its voters, totals and activity log |
| GET | `/events/{id}/voting-link` | `{ token, public_url, results_url }` (frontend URLs) |
| GET | `/events/{id}/results` | The organiser's view of the tally, whatever the public visibility setting |
| GET | `/events/{id}/activity` | Activity log, newest first: `[ { action, description, during_voting, created_at, user } ]` |

**Event body:**
- `name` (string, required on create)
- `description` (string, nullable)
- `selection_mode`: `single` or `multiple`
- `results_visibility`: `live`, `after_close` (default) or `private`
- `starts_at`, `ends_at` (ISO dates, nullable; `ends_at` must not be before `starts_at`)

**Event object adds:**
- `status`: `upcoming`, `active` or `completed`, computed from the voting window
- `selection_mode`
- `ballot_locked`: true once the first vote is cast or voting has closed
- `categories_count`, `candidates_count`, `voters_count`, `voted_count`

## Categories (organiser)

| Method | Path | Body |
|---|---|---|
| GET | `/events/{id}/categories` | |
| POST | `/events/{id}/categories` | `name` (unique per event). Returns `409` when the ballot is locked. |
| PATCH | `/events/{id}/categories/{categoryId}` | `name`. Allowed while locked, and logged. |
| DELETE | `/events/{id}/categories/{categoryId}` | Also deletes its candidates. Returns `409` when locked. |

## Candidates (organiser)

These endpoints take `multipart/form-data`, because they can include a photo.

| Method | Path | Body |
|---|---|---|
| GET | `/events/{id}/candidates` | |
| POST | `/events/{id}/candidates` | `category_id, name, description?` (max 280 chars), `image?` (jpg/png/webp/gif, max 5 MB). Returns `409` when locked. |
| POST or PATCH | `/events/{id}/candidates/{candidateId}` | `category_id?, name?, description?, image?, remove_image?`. Name, description and photo changes are allowed while locked (and logged). Send an empty `description` to clear it. Changing `category_id` returns `409` when locked. |
| DELETE | `/events/{id}/candidates/{candidateId}` | Also deletes the photo. Returns `409` when locked. |

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
| GET | `/public/events/{token}` | Ballot of an **open** event: `{ id, name, description, status, selection_mode, results_visibility, starts_at, ends_at, categories, candidates }` |
| POST | `/public/events/{token}/code/validate` | `{ code }` returns `{ valid: true, name }`, or `422` if invalid/used, or `403` if not open |
| POST | `/public/events/{token}/vote` | `{ code, choices: [ { category_id, candidate_id } ] }` returns `{ message }` |
| GET | `/public/events/{token}/results` | `{ event, stats, integrity: { changes_during_voting }, categories: [ { id, name, candidates: [ { id, name, description, image_url, votes } ], leader } ] }`, or `403 { message, visibility, event }` while results aren't published |

**Not open.** For an event that hasn't started or has ended, `GET /public/events/{token}` returns `403 { message, status, starts_at, ends_at, results_public }`.

**How a ballot is processed:** the server checks each of these before recording anything.
- The event is inside its voting window.
- Every candidate belongs to the event and to the stated category.
- In `single` mode, there is at most one pick per category.
- The code exists and is unused. The check runs under a row lock in the same transaction.

**What gets stored (secret ballot):** each chosen candidate's total goes up by one, and the code is marked as used. No ballot row, choice, timestamp or IP is stored that could link the vote to the voter.

**Rate limits:** code validation and voting allow 30 requests per minute per IP per event. Public reads allow 120 per minute per IP.

## Live results (Laravel Reverb)

When `BROADCAST_CONNECTION=reverb` and results are public, each accepted ballot broadcasts:
- channel: `event.{eventId}` (public)
- event: `vote.cast`
- payload: `{ event_id }` (no ballot content)

The results page refetches the totals when it receives one. Without Reverb it refreshes every 15 seconds.
