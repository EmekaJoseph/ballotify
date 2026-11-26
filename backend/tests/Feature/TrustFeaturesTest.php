<?php

use App\Jobs\SendVoterInvitation;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\ResultCertificate;
use App\Models\User;
use App\Models\VotePurchase;
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
    if ($pro) {
        $event->forceFill(['plan' => 'pro'])->save();
    }
    return $event->fresh();
}

function tfBallot(Event $event): array
{
    $cat = Category::create(['event_id' => $event->id, 'name' => 'President']);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Bola']);
    return [$cat, $a, $b];
}

// ------------------------------------------------------------------ voter self-registration

it('lets voters request their own code with an ID on the list (Pro, optional)', function () {
    Bus::fake();
    $free = tfEvent(pro: false);
    $this->actingAs($free->user)->patchJson("/api/events/{$free->id}", ['self_registration' => true])->assertStatus(402);

    $event = tfEvent();
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['self_registration' => true, 'member_id_label' => 'Matric number'])
        ->assertOk()->assertJsonPath('self_registration', true);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/import", ['voters' => [
        ['name' => 'Ada', 'email' => 'ada@example.com', 'member_id' => 'csc/19/001'],
        ['name' => 'NoContact', 'member_id' => 'CSC/19/002'],
        ['name' => 'Dup', 'member_id' => 'CSC/19/001'],
    ]])->assertStatus(422)->assertJsonValidationErrors('voters.2.member_id');
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/import", ['voters' => [
        ['name' => 'Ada', 'email' => 'ada@example.com', 'member_id' => 'csc/19/001'],
        ['name' => 'NoContact', 'member_id' => 'CSC/19/002'],
    ]])->assertCreated();

    $url = "/api/public/events/{$event->link_token}/request-code";
    $this->getJson("/api/public/events/{$event->link_token}")->assertJsonPath('self_registration', true)->assertJsonPath('member_id_label', 'Matric number');
    $this->postJson($url, ['member_id' => 'NOPE'])->assertNotFound();
    $this->postJson($url, ['member_id' => 'CSC/19/002'])->assertStatus(422); // no contact on file
    $this->postJson($url, ['member_id' => ' csc/19/001 '])->assertOk()->assertJsonPath('sent_to.0', 'ad••@example.com');
    Bus::assertDispatchedAfterResponse(SendVoterInvitation::class, fn ($j) => $j->channels === ['email']);
    $this->postJson($url, ['member_id' => 'CSC/19/001'])->assertStatus(429); // just sent

    Voter::where('member_id', 'CSC/19/001')->update(['used_at' => now()]);
    cache()->flush();
    $this->postJson($url, ['member_id' => 'CSC/19/001'])->assertStatus(409);
});

it('keeps self-registration off unless the organiser switches it on', function () {
    $event = tfEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA2222', 'member_id' => 'M1', 'email' => 'a@example.com']);
    $this->postJson("/api/public/events/{$event->link_token}/request-code", ['member_id' => 'M1'])->assertForbidden();
});

// ------------------------------------------------------------------ candidate self-service

it('gives candidates a private link to edit their own photo and description', function () {
    $event = tfEvent();
    [, $ada] = tfBallot($event);
    $url = $this->actingAs($event->user)->getJson("/api/events/{$event->id}/candidates/{$ada->id}/profile-link")->assertOk()->json('url');
    $token = Str::afterLast($url, '/');

    $this->getJson("/api/public/candidate-profile/{$token}")->assertOk()->assertJsonPath('name', 'Ada')->assertJsonPath('editable', true);
    $this->postJson("/api/public/candidate-profile/{$token}", ['description' => '  Final-year Law  ', 'name' => 'Hacker'])
        ->assertOk()->assertJsonPath('description', 'Final-year Law')->assertJsonPath('name', 'Ada');
    expect(AuditLog::where('action', 'candidate.self_edit')->exists())->toBeTrue()
        ->and($ada->fresh()->toArray())->not->toHaveKey('profile_token');

    $this->actingAs($event->user)->deleteJson("/api/events/{$event->id}/candidates/{$ada->id}/profile-link")->assertNoContent();
    $this->getJson("/api/public/candidate-profile/{$token}")->assertNotFound();
});

// ------------------------------------------------------------------ nominations

it('collects public nominations and lets the organiser add nominees to the ballot', function () {
    $event = tfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 50, 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(5)], pro: false);
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Most Popular']);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['nominations_enabled' => true, 'nominations_ends_at' => now()->addDays(4)->toIso8601String()])
        ->assertStatus(422); // must close before voting opens
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['nominations_enabled' => true, 'nominations_ends_at' => now()->addDays(2)->toIso8601String()])->assertOk();

    $url = "/api/public/events/{$event->link_token}/nominations";
    $this->getJson($url)->assertOk()->assertJsonPath('open', true)->assertJsonPath('categories.0.name', 'Most Popular');
    $a = ['category_id' => $cat->id, 'device_id' => 'phone-a'];
    $this->postJson($url, $a + ['nominee_name' => 'Amaka  Obi', 'reason' => 'Kind'])->assertCreated();
    // Same person (same IP and browser), same name in the same category: refused, any spelling.
    $this->postJson($url, $a + ['nominee_name' => 'amaka obi'])->assertStatus(422)->assertJsonValidationErrors('nominee_name');
    // Someone else on the same network (shared mobile IP, other browser): counts.
    $this->postJson($url, ['category_id' => $cat->id, 'device_id' => 'phone-b', 'nominee_name' => 'amaka obi'])->assertCreated();
    $this->postJson($url, $a + ['nominee_name' => 'Tunde'])->assertCreated();
    $this->postJson($url, $a + ['nominee_name' => 'Zainab'])->assertCreated();
    $this->postJson($url, $a + ['nominee_name' => 'Kemi'])->assertStatus(429); // 3 per person per category

    // The same name in another category is fine.
    $cat2 = Category::create(['event_id' => $event->id, 'name' => 'Best Dressed']);
    $this->postJson($url, ['category_id' => $cat2->id, 'device_id' => 'phone-a', 'nominee_name' => 'Amaka Obi'])->assertCreated();
    // No browser ID (storage blocked): the IP alone decides.
    $this->postJson($url, ['category_id' => $cat2->id, 'nominee_name' => 'Ngozi'])->assertCreated();
    $this->postJson($url, ['category_id' => $cat2->id, 'nominee_name' => 'NGOZI'])->assertStatus(422);

    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/nominations")->assertOk()
        ->assertJsonPath('total', 6)
        ->assertJsonPath('categories.0.nominees.0.count', 2)
        ->assertJsonPath('categories.0.nominees.0.on_ballot', false);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/nominations/promote", ['category_id' => $cat->id, 'name' => 'Amaka Obi'])->assertCreated();
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/nominations/promote", ['category_id' => $cat->id, 'name' => 'AMAKA OBI'])->assertStatus(422);
    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/nominations")->assertJsonPath('categories.0.nominees.0.on_ballot', true);

    $event->update(['nominations_ends_at' => now()->subMinute()]);
    $this->postJson($url, ['category_id' => $cat->id, 'nominee_name' => 'Late'])->assertForbidden();
});

it('lets the organiser suggest nominee names that the public can pick or ignore', function () {
    $event = tfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 50, 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(5),
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
    $this->postJson($url, ['category_id' => $cat->id, 'device_id' => 'p1', 'nominee_name' => 'Burna Boy'])->assertCreated();
    $this->postJson($url, ['category_id' => $cat->id, 'device_id' => 'p2', 'nominee_name' => 'BURNA BOY'])->assertCreated();
    $this->postJson($url, ['category_id' => $cat->id, 'nominee_name' => 'Asake'])->assertCreated();
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

it('runs a full USSD vote with the same rules and a receipt', function () {
    $event = tfEvent();
    [$cat, $ada, $bola] = tfBallot($event);
    $vp = Category::create(['event_id' => $event->id, 'name' => 'Vice President']);
    $chidi = Candidate::create(['event_id' => $event->id, 'category_id' => $vp->id, 'name' => 'Chidi']);

    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['ussd_enabled' => true])->assertOk()->assertJsonPath('ussd_enabled', true);
    $event->refresh();
    expect($event->ussd_code)->toMatch('/^\d{6}$/');
    // New codes are numbers only (keypads can't type letters).
    $code = $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 1])->json('voters.0.code');
    expect($code)->toMatch('/^\d{10}$/');

    $ussd = fn (string $text) => $this->post('/api/ussd/ussd-secret', ['sessionId' => 's1', 'phoneNumber' => '+2348000000000', 'text' => $text])->assertOk()->getContent();
    $this->post('/api/ussd/wrong', ['text' => ''])->assertNotFound();

    expect($ussd(''))->toStartWith('CON Welcome')
        ->and($ussd('999999'))->toStartWith('END Event not found')
        ->and($ussd($event->ussd_code))->toContain('Enter your voting code')
        ->and($ussd("{$event->ussd_code}*0000000000"))->toStartWith('END That code is not valid')
        ->and($ussd("{$event->ussd_code}*{$code}"))->toContain("President (1/2)\n1. Ada\n2. Bola")
        ->and($ussd("{$event->ussd_code}*{$code}*7"))->toContain('Invalid choice')
        ->and($ussd("{$event->ussd_code}*{$code}*2*1"))->toContain("Confirm your vote:\nPresident: Bola\nVice President: Chidi");

    $final = $ussd("{$event->ussd_code}*{$code}*2*1*1");
    expect($final)->toStartWith('END Thank you')->toMatch('/Receipt: [A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{2}/')
        ->and($bola->fresh()->votes)->toBe(1)->and($chidi->fresh()->votes)->toBe(1)->and($ada->fresh()->votes)->toBe(0)
        ->and($ussd("{$event->ussd_code}*{$code}"))->toStartWith('END That code is not valid'); // used once
});

it('converts existing letter codes to numbers when USSD is switched on', function () {
    $event = tfEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'ABCD2345']);
    Voter::create(['event_id' => $event->id, 'code' => 'USED2345', 'used_at' => now()]);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['ussd_enabled' => true]);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/numeric-codes")->assertOk()->assertJsonPath('replaced', 1);
    expect(Voter::where('event_id', $event->id)->whereNull('used_at')->value('code'))->toMatch('/^\d{10}$/')
        ->and(Voter::whereNotNull('used_at')->value('code'))->toBe('USED2345');
});

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

it('verifies the payout account and splits paid votes to the organiser automatically', function () {
    Http::fake([
        'api.paystack.co/bank?*' => Http::response(['status' => true, 'data' => [['name' => 'GTBank', 'code' => '058', 'active' => true]]]),
        'api.paystack.co/bank/resolve*' => Http::response(['status' => true, 'data' => ['account_name' => 'ADA OBI']]),
        'api.paystack.co/subaccount' => Http::response(['status' => true, 'data' => ['subaccount_code' => 'ACCT_abc123']]),
        'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x']]),
    ]);
    $event = tfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 100]);
    [, $ada] = tfBallot($event);
    $owner = $event->user;

    $this->actingAs($owner)->getJson('/api/billing/banks')->assertOk()->assertJsonPath('banks.0.code', '058');
    $this->actingAs($owner)->postJson('/api/billing/resolve-account', ['bank_code' => '058', 'account_number' => '0123456789'])
        ->assertOk()->assertJsonPath('account_name', 'ADA OBI');
    $this->actingAs($owner)->putJson('/api/me/payout-account', ['bank_code' => '058', 'account_number' => '0123456789'])
        ->assertOk()->assertJsonPath('payout_account_name', 'ADA OBI')->assertJsonPath('payout_bank_name', 'GTBank');
    expect($owner->fresh()->paystack_subaccount_code)->toBe('ACCT_abc123');

    $this->postJson("/api/public/events/{$event->link_token}/paid-votes", ['candidate_id' => $ada->id, 'quantity' => 10, 'email' => 'fan@example.com'])->assertOk();
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/transaction/initialize')
        && $r['subaccount'] === 'ACCT_abc123' && $r['transaction_charge'] === 10000 && $r['bearer'] === 'subaccount');

    // Once paid, it counts as already paid out.
    VotePurchase::first()->update(['status' => 'success', 'paid_at' => now()]);
    $this->actingAs($owner)->getJson("/api/events/{$event->id}/earnings")
        ->assertJsonPath('auto_paid_naira', 900)->assertJsonPath('balance_naira', 0)->assertJsonPath('automatic_payouts', true);
});

// ------------------------------------------------------------------ data rights

it('exports the organiser’s data and deletes the account only when nothing is owed', function () {
    $event = tfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 100]);
    $owner = $event->user;
    $this->actingAs($owner)->getJson('/api/me/export')->assertOk()->assertJsonPath('account.email', $owner->email)->assertJsonPath('events.0.name', 'Union Election');

    VotePurchase::create(['event_id' => $event->id, 'candidate_name' => 'Ada', 'quantity' => 10, 'amount_kobo' => 100000, 'fee_kobo' => 10000,
        'reference' => 'PV-OWED1', 'email' => 'f@example.com', 'status' => 'success', 'paid_at' => now()]);
    $this->actingAs($owner)->deleteJson('/api/me', ['password' => 'wrong'])->assertStatus(422);
    $this->actingAs($owner)->deleteJson('/api/me', ['password' => 'password'])->assertStatus(422)->assertJsonValidationErrors('password');

    VotePurchase::query()->update(['subaccount' => 'ACCT_x']); // paid out automatically
    $this->actingAs($owner)->deleteJson('/api/me', ['password' => 'password'])->assertOk();
    expect(User::find($owner->id))->toBeNull()->and(Event::find($event->id))->toBeNull();
});
