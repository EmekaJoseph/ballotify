<?php

use App\Jobs\SendVoterInvitation;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\ResultCertificate;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ballotify.billing.enabled' => true,
        'services.paystack.secret_key' => 'sk_test_secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
        'services.turnstile.secret_key' => null,
        'services.ussd.webhook_secret' => 'ussd-secret',
    ]);
});

function tfEvent(array $attrs = [], bool $pro = true): Event
{
    $event = Event::create(array_merge([
        'user_id' => User::factory()->create()->id,
        'name' => 'Union Election',
        'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'results_visibility' => 'live',
    ], $attrs));
    return $event->fresh();
}

/** One nomination, sent through the public form (one category, one name). */
function nominateOne($test, string $url, array $d)
{
    return $test->postJson("{$url}/batch", array_filter(['device_id' => $d['device_id'] ?? null]) + ['nominations' => [
        ['category_id' => $d['category_id'], 'nominee_name' => $d['nominee_name'], 'reason' => $d['reason'] ?? null],
    ]]);
}

function tfBallot(Event $event): array
{
    $cat = Category::create(['event_id' => $event->id, 'name' => 'President']);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Bola']);
    return [$cat, $a, $b];
}

// ------------------------------------------------------------------ voter self-registration

// ------------------------------------------------------------------ candidate self-service

// ------------------------------------------------------------------ nominations

it('collects public nominations and lets the organiser add nominees to the ballot', function () {
    $event = tfEvent(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(5)], pro: false);
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Most Popular']);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['nominations_enabled' => true, 'nominations_ends_at' => now()->addDays(4)->toIso8601String()])
        ->assertStatus(422); // must close before voting opens
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['nominations_enabled' => true, 'nominations_ends_at' => now()->addDays(2)->toIso8601String()])->assertOk();

    $url = "/api/public/events/{$event->link_token}/nominations";
    $this->getJson($url)->assertOk()->assertJsonPath('open', true)->assertJsonPath('categories.0.name', 'Most Popular');
    $a = ['category_id' => $cat->id, 'device_id' => 'phone-a'];
    nominateOne($this, $url, $a + ['nominee_name' => 'Amaka  Obi', 'reason' => 'Kind'])->assertCreated();
    // Same person (same IP and browser), same name in the same category: refused, any spelling.
    nominateOne($this, $url, $a + ['nominee_name' => 'amaka obi'])->assertStatus(422);
    // Someone else on the same network (shared mobile IP, other browser): counts.
    nominateOne($this, $url, ['category_id' => $cat->id, 'device_id' => 'phone-b', 'nominee_name' => 'amaka obi'])->assertCreated();
    nominateOne($this, $url, $a + ['nominee_name' => 'Tunde'])->assertCreated();
    nominateOne($this, $url, $a + ['nominee_name' => 'Zainab'])->assertCreated();
    nominateOne($this, $url, $a + ['nominee_name' => 'Kemi'])->assertStatus(422); // 3 per person per category

    // The same name in another category is fine.
    $cat2 = Category::create(['event_id' => $event->id, 'name' => 'Best Dressed']);
    nominateOne($this, $url, ['category_id' => $cat2->id, 'device_id' => 'phone-a', 'nominee_name' => 'Amaka Obi'])->assertCreated();
    // No browser ID (storage blocked): the IP alone decides.
    nominateOne($this, $url, ['category_id' => $cat2->id, 'nominee_name' => 'Ngozi'])->assertCreated();
    nominateOne($this, $url, ['category_id' => $cat2->id, 'nominee_name' => 'NGOZI'])->assertStatus(422);

    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/nominations")->assertOk()
        ->assertJsonPath('total', 6)
        ->assertJsonPath('categories.0.nominees.0.count', 2)
        ->assertJsonPath('categories.0.nominees.0.on_ballot', false);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/nominations/promote", ['category_id' => $cat->id, 'name' => 'Amaka Obi'])->assertCreated();
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/nominations/promote", ['category_id' => $cat->id, 'name' => 'AMAKA OBI'])->assertStatus(422);
    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/nominations")->assertJsonPath('categories.0.nominees.0.on_ballot', true);

    $event->update(['nominations_ends_at' => now()->subMinute()]);
    nominateOne($this, $url, ['category_id' => $cat->id, 'nominee_name' => 'Late'])->assertForbidden();
});

it('takes the whole nomination form at once: every category, several names each', function () {
    $event = tfEvent(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(5),
        'nominations_enabled' => true, 'nominations_ends_at' => now()->addDays(2)], pro: false);
    $artist = Category::create(['event_id' => $event->id, 'name' => 'Best Artist']);
    $dressed = Category::create(['event_id' => $event->id, 'name' => 'Best Dressed']);
    $url = "/api/public/events/{$event->link_token}/nominations";

    // The form lists the categories and the per-category limit.
    $this->getJson($url)->assertJsonCount(2, 'categories')->assertJsonPath('per_category', 3);

    $row = fn ($cat, $name) => ['category_id' => $cat->id, 'nominee_name' => $name];
    $this->postJson("{$url}/batch", ['device_id' => 'phone-a', 'nominations' => [
        $row($artist, 'Burna Boy'), $row($artist, 'Tems'), $row($artist, 'TEMS'),   // a repeat inside the form
        $row($dressed, 'Burna Boy'),                                                 // same name, other category: fine
        ['category_id' => 999999, 'nominee_name' => 'Someone'],                      // not a category of this event
    ]])->assertCreated()
        ->assertJsonCount(3, 'accepted')
        ->assertJsonCount(2, 'refused')
        ->assertJsonPath('refused.0.name', 'TEMS')
        ->assertJsonPath('refused.1.category', null);

    // The per-category limit counts across visits: 2 already in Best Artist, so 1 more fits.
    $this->postJson("{$url}/batch", ['device_id' => 'phone-a', 'nominations' => [$row($artist, 'Asake'), $row($artist, 'Rema')]])
        ->assertCreated()->assertJsonPath('accepted.0.name', 'Asake')->assertJsonPath('refused.0.name', 'Rema');

    // Nothing accepted: 422 with the reasons.
    $this->postJson("{$url}/batch", ['device_id' => 'phone-a', 'nominations' => [$row($artist, 'Burna Boy')]])
        ->assertStatus(422)->assertJsonCount(1, 'refused');
    $this->postJson("{$url}/batch", ['nominations' => []])->assertStatus(422);

    expect(\App\Models\Nomination::where('event_id', $event->id)->count())->toBe(4);
});

it('lets the organiser suggest nominee names that the public can pick or ignore', function () {
    $event = tfEvent(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(5),
        'nominations_enabled' => true, 'nominations_ends_at' => now()->addDays(2)], pro: false);
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Best Artist']);
    $api = "/api/events/{$event->id}/nominations/suggestions";

    // Cleaned up: spaces squeezed, duplicates (any case) dropped, too-short names ignored.
    $this->actingAs($event->user)->putJson($api, ['category_id' => $cat->id, 'names' => ['  Burna   Boy ', 'Tems', 'burna boy', 'x', '']])
        ->assertOk()->assertJsonPath('suggestions', ['Burna Boy', 'Tems']);
    expect(AuditLog::where('action', 'nomination.suggestions')->count())->toBe(1);

    $url = "/api/public/events/{$event->link_token}/nominations";
    $this->getJson($url)->assertJsonPath('categories.0.suggestions', ['Burna Boy', 'Tems']);

    // Picking a suggestion and typing your own both work, and count together with other spellings.
    nominateOne($this, $url, ['category_id' => $cat->id, 'device_id' => 'p1', 'nominee_name' => 'Burna Boy'])->assertCreated();
    nominateOne($this, $url, ['category_id' => $cat->id, 'device_id' => 'p2', 'nominee_name' => 'BURNA BOY'])->assertCreated();
    nominateOne($this, $url, ['category_id' => $cat->id, 'nominee_name' => 'Asake'])->assertCreated();
    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/nominations")
        ->assertJsonPath('categories.0.suggestions', ['Burna Boy', 'Tems'])
        ->assertJsonPath('categories.0.nominees.0.name', 'Burna Boy')
        ->assertJsonPath('categories.0.nominees.0.count', 2)
        ->assertJsonPath('categories.0.nominees.1.name', 'Asake');

    // Emptying the list removes it.
    $this->actingAs($event->user)->putJson($api, ['category_id' => $cat->id, 'names' => []])->assertOk();
    expect($cat->fresh()->nominee_suggestions)->toBeNull();

    // Only the organiser's team, and only this event's categories.
    $other = tfEvent();
    $otherCat = Category::create(['event_id' => $other->id, 'name' => 'Elsewhere']);
    $this->actingAs($event->user)->putJson($api, ['category_id' => $otherCat->id, 'names' => ['A b']])->assertNotFound();
    $this->actingAs(User::factory()->create())->putJson($api, ['category_id' => $cat->id, 'names' => ['A b']])->assertNotFound(); // strangers can't even see the event
});

// ------------------------------------------------------------------ USSD voting

// ------------------------------------------------------------------ certificates

it('certifies final results and lets anyone verify them', function () {
    $event = tfEvent();
    [, $ada] = tfBallot($event);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/certificates")->assertStatus(422); // still open

    $ada->forceFill(['votes' => 7])->save();
    $event->update(['ends_at' => now()->subMinute()]);
    $serial = $this->actingAs($event->user)->postJson("/api/events/{$event->id}/certificates")->assertCreated()
        ->assertJsonPath('payload.categories.0.winners', ['Ada'])->json('serial');
    // Same totals: no duplicate certificate.
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/certificates")->assertOk()->assertJsonPath('serial', $serial);
    // Certifying is not a change to the ballot.
    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/results")->assertJsonPath('integrity.changes_during_voting', 0);

    $this->getJson("/api/public/certificates/{$serial}")->assertOk()->assertJsonPath('valid', true)->assertJsonPath('matches_current', true);

    $ada->forceFill(['votes' => 9])->save();
    $this->getJson("/api/public/certificates/{$serial}")->assertJsonPath('valid', true)->assertJsonPath('matches_current', false);

    // Editing the stored certificate breaks its signature.
    $cert = ResultCertificate::first();
    $payload = $cert->payload;
    $payload['categories'][0]['candidates'][0]['votes'] = 999;
    DB::table('result_certificates')->where('id', $cert->id)->update(['payload' => json_encode($payload)]);
    $this->getJson("/api/public/certificates/{$serial}")->assertJsonPath('valid', false);
    $this->getJson('/api/public/certificates/BLT-NONE-0000')->assertNotFound();
});

// ------------------------------------------------------------------ bot protection

it('requires the Turnstile check when it is configured', function () {
    config(['services.turnstile.secret_key' => 'ts_secret']);
    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
        ->push(['success' => false])
        ->push(['success' => true])]);
    $body = ['name' => 'Bot', 'email' => 'bot@example.com', 'password' => 'password123', 'terms' => true];

    $this->postJson('/api/auth/register', $body)->assertStatus(422)->assertJsonValidationErrors('captcha_token');
    $this->postJson('/api/auth/register', $body + ['captcha_token' => 'bad'])->assertStatus(422);
    $this->postJson('/api/auth/register', $body + ['captcha_token' => 'good'])->assertCreated();
});

// ------------------------------------------------------------------ automatic payouts

// ------------------------------------------------------------------ data rights

it('exports the organiser’s data and deletes the account with the right password', function () {
    $event = tfEvent();
    $owner = $event->user;
    $this->actingAs($owner)->getJson('/api/me/export')->assertOk()->assertJsonPath('account.email', $owner->email)->assertJsonPath('events.0.name', 'Union Election');

    $this->actingAs($owner)->deleteJson('/api/me', ['password' => 'wrong'])->assertStatus(422);
    $this->actingAs($owner)->deleteJson('/api/me', ['password' => 'password'])->assertOk();
    expect(User::find($owner->id))->toBeNull()->and(Event::find($event->id))->toBeNull();
});
