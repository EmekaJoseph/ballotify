# Ballotify API

Base URL

- Local development: http://localhost:8883/api
- Public endpoints are under /api/public
- Authenticated admin endpoints require Bearer token in Authorization header

Authentication

- POST /auth/register
    - Body: { name, email, password }
    - Response: { token, user }
- POST /auth/login
    - Body: { email, password }
    - Response: { token, user }
- POST /auth/logout
    - Auth required
    - Response: { message }
- GET /me
    - Auth required
    - Response: user object

Events (Admin)

- GET /events
    - Auth required
    - Returns events owned by the user
- POST /events
    - Auth required
    - Body: { name, expected_voters?: number, starts_at?: ISO, ends_at?: ISO, event_type_id?: number }
    - Response: created event
- GET /events/{event}
    - Auth required
    - Response: event with categories and candidates
- PATCH /events/{event}
    - Auth required
    - Body: any of { name, expected_voters, starts_at, ends_at, event_type_id }
    - Response: updated event
- DELETE /events/{event}
    - Auth required
    - Response: 204 No Content
- GET /events/{event}/voting-link
    - Auth required
    - Response: { public_url }

Categories (Admin)

- POST /events/{event}/categories
    - Auth required
    - Body: { name }
    - Response: created category

Candidates (Admin)

- POST /events/{event}/candidates
    - Auth required
    - multipart/form-data:
        - category_id (int, required)
        - name (string, required)
        - image (file, optional)
    - Stores images to backend/public/candidates and returns relative path in image_path
    - Response: created candidate
- PATCH /events/{event}/candidates/{candidate}
    - Auth required
    - multipart/form-data: any of
        - category_id (int)
        - name (string)
        - image (file)
    - Replaces existing image on disk if a new one is provided
    - Response: updated candidate
- DELETE /events/{event}/candidates/{candidate}
    - Auth required
    - Deletes candidate and its image file (if present)
    - Response: 204 No Content

Voters (Admin)

- GET /events/{event}/voters
    - Auth required
    - Response: list of voters for the event
- POST /events/{event}/voters
    - Auth required
    - Body: { code: string, name?: string }
    - Plaintext codes are unique per event; validation returns 422 on duplicates
    - Response: created voter
- PATCH /events/{event}/voters/{voter}
    - Auth required
    - Body: any of { name?: string|null, code?: string, used_at?: string|null }
    - Set used_at to null/empty string to reset a used voter
    - Response: updated voter
- DELETE /events/{event}/voters/{voter}
    - Auth required
    - Response: 204 No Content
- POST /events/{event}/voters/generate
    - Auth required
    - Body: { count?: number (1..10000), names?: string[] }
    - Generates codes in plaintext; returns array of { name, code }
- POST /events/{event}/voters/import
    - Auth required
    - Body: { names: string[] }
    - Imports voters and generates codes; returns generated codes

Public (Voters)

- GET /public/events/{token}
    - Returns public event data (categories and candidates) by link token
- POST /public/events/{token}/code/validate
    - Body: { code: string }
    - Returns 200 { valid: true } if code exists and is unused; otherwise 422 { valid: false }
- POST /public/events/{event}/code/validate
    - Body: { code: string }
    - Same validation as above but by event_id instead of token
- POST /public/events/{token}/vote
    - Body: { code: string, choices: [{ category_id, candidate_id }, ...] }
    - For single-choice events: only one candidate per category is accepted.
    - For multiple-choice events: multiple candidates per category are accepted; duplicate pairs are ignored.
    - Validates code (unused), categories and candidates belong to the event, and candidate-category pairs are correct
    - Records votes accordingly and sets used_at on the voter
    - Response: { message: "Vote recorded" }

Data Notes

- Voter codes are stored in plaintext in voters.code and must be unique per event
- Event type controls per-category selection:
    - event_types: keys "single" or "multiple" referenced by events.event_type_id
    - Single: one candidate per category
    - Multiple: many candidates per category
- Uniqueness enforced on (voter_id, category_id, candidate_id) to prevent duplicate identical votes
- Once a vote is submitted, the voter is marked as used (used_at set)

Errors

- Standard validation errors return 422 with error messages
- Unauthorized requests return 401/403
- Not found resources return 404

Images

- Candidate images are saved to backend/public/candidates
- Clients should render using: http://<backend-host>/{image_path}

Broadcasting

- When a vote is recorded successfully, a broadcast event is emitted:
    - Channel: event.{event_id} (public channel)
    - Event name: vote.cast
    - Payload:
        - event_id: number
        - choices: [{ category_id: number, candidate_id: number }, ...]
        - time: ISO timestamp
- Configure Pusher (example .env):
    - BROADCAST_DRIVER=pusher
    - PUSHER_APP_ID=your-app-id
    - PUSHER_APP_KEY=your-key
    - PUSHER_APP_SECRET=your-secret
    - PUSHER_HOST=
    - PUSHER_PORT=443
    - PUSHER_SCHEME=https
    - PUSHER_APP_CLUSTER=mt1

Seeding

- Three events seeded with 4 categories each and 3 candidates per category
- 200 voters per event with codes of the form E{eventId}-0001..E{eventId}-0200
- Sample votes are created for the first 20 voters in each event (used)

Examples

- Create candidate (multipart):
    - POST /api/events/1/candidates
    - Form fields: category_id=2, name="John Doe", image=@/path/to.jpg
    - Response: { id, event_id, category_id, name, image_path }
- Validate code by event:
    - POST /api/public/events/1/code/validate
    - Body: { "code": "E1-0021" }
    - Response: { "valid": true }
