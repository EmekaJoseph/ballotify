# Ballotify mobile app plan

Status: **planned, not started** (written 2026-11-26). Shared copy: https://claude.ai/artifact/FYAkcBevSy9AmbdhddQrKh

Build a Flutter app for organisers' everyday work, in `mobile/` in this repo, on the existing Ballotify API. Payments stay on the website. Estimate: about 10–12 weeks in five stages, plus four small backend additions.

## Scope

The app is for organisers, the people who pay. Voters keep using the voting link (or USSD): nobody installs an app to vote once.

| Area | In the app | Stays on the website |
| --- | --- | --- |
| Account | Sign in, two-step code, sign out, profile, language | Sign-up, email confirmation, password reset, data export, account deletion |
| Events | List, create (from a template), edit details and dates, open/close, duplicate | Branding (logo and colour), delete event |
| Ballot | Categories, motions, candidates (one-line Add form), photos from the camera, voter groups per category | Candidate import from file, ballot PDF |
| Voters | List, search, filters, Add form, duplicates, delete (one or ticked), import from a phone file, send or resend codes (email, SMS, WhatsApp share), proxies | Numeric USSD codes |
| Results | Live results, turnout, quorum progress, rounds for ranked choice | PDF report, certificates |
| Activity | Activity log | — |
| Team | View co-organisers | Invite and change roles |
| Plans and money | Show the plan and its limits, SMS balance | Every payment: upgrades, SMS credits, payout account, earnings |
| Platform admin | — | All of Admin |

The app works fully with whatever plan an event has; it never sells or links to a plan.

## Payments and store rules

No purchase happens in the app, so Apple and Google billing (and their 15–30% cut) never applies. Both stores also restrict apps from steering users to buy elsewhere, so the app shows plans but never sells them:

- Shows the plan and its limits, e.g. "Pro Medium · 412 / 1,000 voters", and the SMS balance.
- At a limit, states it neutrally: "This event has reached its voter limit." No upgrade button, price or link.
- After the organiser pays on the website, pull-to-refresh shows the new plan.
- The API already returns limits with every event; the app ignores the `upgrade` hints the website uses.

Open question: the rules change often (Apple's link-out rules differ by country). Check both stores' current payment guidelines before the first submission.

## Screens

Fourteen screens, all on endpoints that exist today except push registration (see Backend additions).

| Screen | What it does | API endpoints |
| --- | --- | --- |
| Sign in | Email and password, then the two-step code if on | `POST /auth/login`, `POST /auth/two-factor` |
| Events | My events and shared ones, status, plan badge | `GET /events` |
| New event | Name, type, dates, template | `GET /event-templates`, `POST /events` |
| Event overview | Status, turnout, plan and limits, open or close voting, voting link and QR | `GET /events/{id}`, `PATCH /events/{id}`, `GET /events/{id}/voting-link` |
| Ballot | Categories, motions, voting method, groups | `GET/POST/PATCH/DELETE /events/{id}/categories` |
| Add candidates | One-line form and list, duplicates refused | `POST /events/{id}/candidates/import` |
| Candidate | Edit name, description, photo from camera or gallery | `PATCH/DELETE /events/{id}/candidates/{c}` |
| Voters | Search, filters, duplicates, tick and delete, proxies | `GET /events/{id}/voters`, `POST /voters/bulk-delete`, `PATCH /voters/{v}` |
| Add voters | One-line form and list, or a file from the phone | `POST /events/{id}/voters/import` |
| Send codes | Email or SMS to all, pending or ticked; WhatsApp share sheet | `POST /voters/invite`, `POST /voters/remind`, `POST /voters/{v}/shared`, `GET /messaging/status` |
| Results | Live tallies, turnout, quorum, ranked rounds | `GET /events/{id}/results`, live updates over Reverb |
| Nominations | Nominees per category, suggested names, add to ballot | `GET /nominations`, `PUT /nominations/suggestions`, `POST /nominations/promote` |
| Activity | Activity log | `GET /events/{id}/activity` |
| Settings | Voting options, meeting rules, reset votes (owner) | `PATCH /events/{id}`, `POST /events/{id}/reset-votes` |

All five languages (English, French, Yoruba, Hausa, Igbo) come from the website's existing translations (`frontend/app/i18n/messages.ts`).

## Backend additions

The app needs four small changes to the Laravel API, about one week together. Everything else it uses already exists.

1. **Sign-ins that last on a phone.** Tokens now expire after 7 days (`SANCTUM_EXPIRATION`), so the app would sign people out weekly. Add per-device tokens (90 days, renewed on use), named by device, with a "Signed-in devices" list to revoke them.
2. **Push notifications.** None exist today. Add a device-token table, register and unregister endpoints, and sending through Firebase Cloud Messaging (it reaches iPhones through Apple's service too). First notifications: voting opened, voting closed, quorum reached, invitations failed to deliver, nominations closing.
3. **Store-safe limit messages.** Today's limit errors say "Upgrade this event to Pro Large…". When the request comes from the app, return the same limit without upgrade wording or prices.
4. **Minimum app version.** A small endpoint the app checks on start, so an old app can be asked to update when the API changes.

## Architecture

The app shares the website's API; Firebase push is the only new service.

```
   Ballotify app (Flutter)  [new]          Website (Nuxt)
   organisers, Android then iPhone         voters, organisers, payments
              |  REST, device token              |  REST, same endpoints
              +----------------+  +--------------+
                               v  v
                      Laravel API (existing)
                 same endpoints, plus 4 small additions
                               |
      +---------------+--------+--------+-----------------+
      v               v                 v                 v
 MySQL database     Reverb       Firebase messaging    Paystack
 events, voters,  live results   push alerts [new]    payments via
 votes                                                 website
```

The app talks only to the Laravel API, exactly like the website. Checkout still opens from the website.

## Technology

Flutter 3.50 (installed on the dev machine), one codebase for Android and iPhone. Android first: it is most of the Nigerian market and needs no Mac to build.

| Need | Package or approach |
| --- | --- |
| API calls | `dio`, with the token added to every request and sign-out on a 401 (as the website does) |
| State | `riverpod` |
| Navigation | `go_router`, with deep links from notifications to the right event |
| Token storage | `flutter_secure_storage` (Keychain / Keystore) |
| Live results | Reverb speaks the Pusher protocol: a Pusher-compatible Dart client, falling back to refreshing every 15 s like the website |
| Push | `firebase_messaging` |
| Photos | `image_picker` (camera or gallery) |
| Excel and CSV files | `file_picker` + an `.xlsx` reader, same column matching as the website |
| Sharing codes | `share_plus` (WhatsApp and other apps) |
| Languages | `flutter_localizations` + the website's five dictionaries, converted to ARB files |

Layout: `mobile/lib/` split into `api/` (one client per endpoint group, mirroring the website's), `features/` (one folder per screen group) and `shared/` (theme, widgets, duplicate checks ported from `frontend/app/utils/duplicates.ts`).

## Stages

Five stages, about 10–12 weeks for one developer. Each ends with a build organisers can try.

1. **Foundation (2 weeks).** `mobile/` project, theme matching the website, sign-in with two-step code, events list, event overview, open and close voting. Backend: device sign-ins (1) and minimum version (4).
2. **Ballot (2 weeks).** Categories and motions, one-line Add candidates, photos from the camera, nominations with suggested names.
3. **Voters (3 weeks).** Voter list, search and filters, Add voters, import a file from the phone, duplicate warnings, delete one or ticked, proxies, send codes by email, SMS or WhatsApp. Backend: store-safe limit messages (3).
4. **Results and settings (1–2 weeks).** Live results, turnout, quorum, ranked rounds, activity log, voting options, meeting rules, reset votes.
5. **Notifications and release (2–3 weeks).** Backend: push notifications (2). All five languages, Play Store internal testing with two or three pilot organisers, then the public Play Store release; the App Store follows once a Mac or cloud build is set up.

## Risks and open questions

- **Two apps to maintain.** Every new organiser feature needs building twice. Mitigation: keep the app to the scope above, and ship web-only features first.
- **Store review.** A rejection over plan wording can delay a release by days. Mitigation: no prices or upgrade wording anywhere in the app (Backend addition 3).
- **iPhone builds need a Mac** or a paid cloud build service. Open question: which, and when?
- **Store accounts.** Google Play costs a one-off US$25 and Apple US$99 a year, both in the business's name. Open question: whose name, and is the company registered?
- **Live results on poor networks.** Reverb needs a steady connection. Mitigation: fall back to refreshing every 15 seconds, as the website does.
- **Should voters ever use the app?** This plan says no; revisit only if organisers ask for it.
