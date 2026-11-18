# Ballotify API reference

- **Base URL:** `{APP_URL}/api` (local default `http://localhost:8883/api`)
- **Format:** JSON. Send `Accept: application/json`.
- **Auth:** admin routes need `Authorization: Bearer <token>` from login/register.
- **Errors:**
  - `422` validation: `{ message, errors: { field: [msg] } }`
  - `401` not signed in
  - `404` not found, or not your event
  - `403` event not open
  - `429` rate limited

## Authentication

| Method | Path | Body | Response |
|---|---|---|---|
| POST | `/auth/register` | `name, email, password (min 8)` | `201 { token, user }` |
| POST | `/auth/login` | `email, password` | `{ token, user }` |
| POST | `/auth/logout` | Bearer token | `{ message }` (revokes the token) |
| GET | `/me` | | `user` |
| PATCH | `/me` | `name?, password?, current_password (required with password)` | `user` (the email can't be changed; sending `email` returns `422`) |

Login and register are limited to 10 requests per minute per IP.

## Events (admin)

| Method | Path | Notes |
|---|---|---|
| GET | `/events` | Your events, newest first, with counts |
| POST | `/events` | Create (body below) |
| GET | `/events/{id}` | Event with `categories` and `candidates` (voters are fetched separately) |
| PATCH | `/events/{id}` | Partial update |
| DELETE | `/events/{id}` | Deletes the event with its codes and votes |
| GET | `/events/{id}/voting-link` | `{ token, public_url, results_url }` (frontend URLs) |

**Event body:**
- `name` (string, required on create)
- `description` (string, nullable)
- `selection_mode`: `single` or `multiple`
- `expected_voters` (int)
- `starts_at`, `ends_at` (ISO dates, nullable; `ends_at` must not be before `starts_at`)

**Event object adds:**
- `status`: `upcoming`, `active` or `completed`, computed from the voting window
- `selection_mode`
- `categories_count`, `candidates_count`, `voters_count`, `voted_count`

## Categories (admin)

| Method | Path | Body |
|---|---|---|
| GET | `/events/{id}/categories` | |
| POST | `/events/{id}/categories` | `name` (unique per event) |
| PATCH | `/events/{id}/categories/{categoryId}` | `name` |
| DELETE | `/events/{id}/categories/{categoryId}` | Also deletes its candidates and votes |

## Candidates (admin)

These endpoints take `multipart/form-data`, because they can include a photo.

| Method | Path | Body |
|---|---|---|
| GET | `/events/{id}/candidates` | |
| POST | `/events/{id}/candidates` | `category_id, name, image?` (jpg/png/webp/gif, max 5 MB) |
| POST or PATCH | `/events/{id}/candidates/{candidateId}` | `category_id?, name?, image?, remove_image?` |
| DELETE | `/events/{id}/candidates/{candidateId}` | Also deletes the photo |

Names are unique within a category. Candidate objects include `image_url` (an absolute URL, or null). Use **POST** for updates that include a file, because PHP does not parse multipart bodies on PATCH.

## Voters / codes (admin)

| Method | Path | Body | Response |
|---|---|---|---|
| GET | `/events/{id}/voters` | | `[ { id, name, email, phone, code, used_at, invited_at, invite_error } ]` |
| POST | `/events/{id}/voters` | `name?, email?, phone?, code?` (generated if omitted) | voter |
| POST | `/events/{id}/voters/generate` | `count (1–10000)` | `201 { voters: [ { name, email, phone, code } ] }` |
| POST | `/events/{id}/voters/import` | `voters: [ { name?, email?, phone? } ]` (max 10000; legacy `names: string[]` also accepted) | `201 { voters: [ { name, email, phone, code } ] }` |
| PATCH | `/events/{id}/voters/{voterId}` | `name?, email?, phone?, code?, used_at?` | voter |
| DELETE | `/events/{id}/voters/{voterId}` | | `204` |
| POST | `/events/{id}/voters/invite` | `channel: email\|sms\|both, audience: not_invited\|pending\|selected, voter_ids?` | `{ queued, email, sms, failed, queue }` |
| GET | `/messaging/status` | | `{ email: { live, provider }, sms: { live, provider }, queue }` |

How contacts are handled:
- **Emails** are lower-cased.
- **Phones** are normalised to international format (`+234…`). Local numbers are converted using `SMS_DEFAULT_COUNTRY_CODE`.
- **Import errors** name the exact row, e.g. `voters.3.email`.

How invitations are sent:
- Each one is a queued job, so it needs the worker (`composer dev` or `php artisan queue:work`). With `QUEUE_CONNECTION=sync`, messages are sent during the request.
- Voters who have voted are skipped, and so are voters with no contact on the chosen channel.
- Invitations can't be sent after voting has closed.
- **Success** sets `invited_at`.
- **Failure** saves the reason in `invite_error` and never raises an error to the caller.

How codes behave:
- Codes are stored and matched **upper-case**, so voters can type them in any case.
- Generated codes have 8 characters with no look-alikes (0/O, 1/I/L).
- Setting `used_at: null` re-enables a code **and discards its previous ballot**.

## Public (voters)

| Method | Path | Notes |
|---|---|---|
| GET | `/public/events/{token}` | Ballot of an **open** event: `{ id, name, description, status, selection_mode, starts_at, ends_at, categories, candidates }` |
| POST | `/public/events/{token}/code/validate` | `{ code }` returns `{ valid: true }`, or `422` if invalid/used, or `403` if not open |
| POST | `/public/events/{token}/vote` | `{ code, choices: [ { category_id, candidate_id } ] }` returns `{ message }` |
| GET | `/public/events/{token}/results` | `{ event: { id, name, status, … }, stats: { total_votes, total_voters, voted, total_candidates }, categories: [ { id, name, candidates: [ { id, name, image_url, votes } ], leader } ] }` |

**Not open.** For an event that hasn't started or has ended, `GET /public/events/{token}` returns `403 { message, status, starts_at, ends_at }`.

**How a ballot is processed:** the server checks each of these before recording anything.
- The event is inside its voting window.
- Every candidate belongs to the event and to the stated category.
- In `single` mode, there is at most one pick per category.
- The code exists and is unused. The check runs under a row lock inside the same transaction that records the votes.

**Rate limits:** code validation and voting allow 30 requests per minute per IP per event. Public reads allow 120 per minute per IP.

## Live results (broadcast)

When `BROADCAST_CONNECTION=pusher`, each accepted ballot broadcasts:
- channel: `event.{eventId}` (public)
- event: `vote.cast`
- payload: `{ event_id, choices, time }` (no voter information)

The results page refetches when it receives one.
