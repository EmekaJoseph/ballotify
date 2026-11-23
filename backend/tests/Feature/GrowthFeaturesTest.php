<?php

use App\Jobs\SendVoterInvitation;
use App\Mail\PaymentReceipt;
use App\Mail\TeamInvitation;
use App\Mail\VotePurchaseReceipt;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Payment;
use App\Models\User;
use App\Models\VotePurchase;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ballotify.billing.enabled' => true,
        'services.paystack.secret_key' => 'sk_test_secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);
});

function gfEvent(array $attrs = [], ?User $owner = null): Event
{
    $owner ??= User::factory()->create();
    return Event::create(array_merge([
        'user_id' => $owner->id,
        'name' => 'Awards',
        'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'results_visibility' => 'live',
    ], $attrs));
}

function gfPro(Event $event): Event
{
    $event->forceFill(['plan' => 'pro'])->save();
    return $event->fresh();
}

function gfBallot(Event $event): array
{
    $category = Category::create(['event_id' => $event->id, 'name' => 'Best Voice']);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Bola']);
    return [$category, $a, $b];
}

// ------------------------------------------------------------------ receipts

it('gives each counted ballot a receipt that can be checked, without linking it to the voter', function () {
    $event = gfEvent();
    [$category, $a] = gfBallot($event);
    Voter::create(['event_id' => $event->id, 'code' => 'CODE2345']);

    $receipt = $this->postJson("/api/public/events/{$event->link_token}/vote", [
        'code' => 'CODE2345', 'choices' => [['category_id' => $category->id, 'candidate_id' => $a->id]],
    ])->assertOk()->json('receipt');

    expect($receipt)->toMatch('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{2}$/');
    // Only a hash is stored, with no voter or candidate column.
    expect(array_keys(BallotReceipt::first()->getAttributes()))->toBe(['id', 'event_id', 'code_hash', 'created_at']);

    $this->postJson("/api/public/events/{$event->link_token}/receipt", ['receipt' => strtolower($receipt)])
        ->assertOk()->assertJsonPath('valid', true);
    $this->postJson("/api/public/events/{$event->link_token}/receipt", ['receipt' => 'ZZZZ-ZZZZ-ZZ'])
        ->assertNotFound()->assertJsonPath('valid', false);
});

// ------------------------------------------------------------------ pick limits, shuffle, templates

it('uses each category’s own pick limit (choose one by default)', function () {
    $event = gfEvent();
    [$category, $a, $b] = gfBallot($event);
    Voter::create(['event_id' => $event->id, 'code' => 'CODE2345']);
    $both = ['code' => 'CODE2345', 'choices' => [
        ['category_id' => $category->id, 'candidate_id' => $a->id],
        ['category_id' => $category->id, 'candidate_id' => $b->id],
    ]];

    $this->postJson("/api/public/events/{$event->link_token}/vote", $both)->assertStatus(422);
    $category->update(['max_choices' => null]);
    $this->postJson("/api/public/events/{$event->link_token}/vote", $both)->assertOk();
});

it('can show candidates in random order without exposing the setting', function () {
    $event = gfEvent();
    [$category] = gfBallot($event);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}/categories/{$category->id}", ['shuffle_candidates' => true])
        ->assertOk()->assertJsonPath('shuffle_candidates', true);

    $ballot = $this->getJson("/api/public/events/{$event->link_token}")->assertOk();
    expect($ballot->json('categories.0'))->not->toHaveKey('shuffle_candidates')
        ->and($ballot->json('candidates'))->toHaveCount(2);
});

it('creates an event from a template', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/event-templates')->assertOk()->assertJsonFragment(['key' => 'agm']);

    $id = $this->actingAs($user)->postJson('/api/events', ['name' => 'Club AGM', 'template' => 'agm'])
        ->assertCreated()->assertJsonPath('categories_count', 5)->json('id');
    $motion = Category::where('event_id', $id)->where('method', 'motion')->first();
    expect($motion->candidates()->pluck('motion_choice')->all())->toBe(['yes', 'no', 'abstain'])
        ->and(Category::where('event_id', $id)->where('method', 'motion')->count())->toBe(2);

    // Church template: council (pick up to 5) and a motion.
    $church = $this->actingAs($user)->postJson('/api/events', ['name' => 'Parish elections', 'template' => 'church'])->assertCreated()->json('id');
    expect(Category::where('event_id', $church)->where('name', 'Church Council')->value('max_choices'))->toBe(5)
        ->and(Category::where('event_id', $church)->where('method', 'motion')->count())->toBe(1);

    $this->actingAs($user)->postJson('/api/events', ['name' => 'Awards', 'template' => 'award_night'])
        ->assertCreated()->assertJsonPath('voting_mode', 'paid')->assertJsonPath('vote_price_naira', 50);
});

// ------------------------------------------------------------------ co-organisers

it('keeps co-organisers a Pro feature', function () {
    $event = gfEvent();
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/members", ['email' => 'x@example.com', 'role' => 'viewer'])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'team');
});

it('gives each co-organiser role the right access', function () {
    Mail::fake();
    $event = gfPro(gfEvent());
    $viewer = User::factory()->create(['email' => 'viewer@example.com']);
    $helper = User::factory()->create(['email' => 'helper@example.com']);
    $stranger = User::factory()->create();
    $owner = $event->user;

    $this->actingAs($owner)->postJson("/api/events/{$event->id}/members", ['email' => 'viewer@example.com', 'role' => 'viewer'])
        ->assertCreated()->assertJsonPath('joined', true);
    $this->actingAs($owner)->postJson("/api/events/{$event->id}/members", ['email' => 'HELPER@example.com', 'role' => 'voters'])->assertCreated();
    Mail::assertSent(TeamInvitation::class, 2);

    // Shared events appear in the member's list with their role.
    $this->actingAs($viewer)->getJson('/api/events')->assertOk()
        ->assertJsonPath('0.id', $event->id)->assertJsonPath('0.my_role', 'viewer')->assertJsonPath('0.abilities', ['view']);

    // Viewer: read only.
    $this->actingAs($viewer)->getJson("/api/events/{$event->id}/results")->assertOk();
    $this->actingAs($viewer)->patchJson("/api/events/{$event->id}", ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($viewer)->getJson("/api/events/{$event->id}/voters")->assertForbidden();

    // Voter manager: voters yes, ballot no, delete no.
    $this->actingAs($helper)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 2])->assertCreated();
    $this->actingAs($helper)->postJson("/api/events/{$event->id}/categories", ['name' => 'New'])->assertForbidden();
    $this->actingAs($helper)->deleteJson("/api/events/{$event->id}")->assertForbidden();

    // Outsiders can't even see it exists.
    $this->actingAs($stranger)->getJson("/api/events/{$event->id}")->assertNotFound();
});

it('links an invitation once the invitee confirms the same email', function () {
    Mail::fake();
    $event = gfPro(gfEvent());
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/members", ['email' => 'new@example.com', 'role' => 'editor'])
        ->assertCreated()->assertJsonPath('joined', false);

    $newcomer = User::factory()->unverified()->create(['email' => 'new@example.com']);
    EventMember::claimFor($newcomer);
    expect(EventMember::first()->user_id)->toBeNull(); // not confirmed yet

    $newcomer->markEmailAsVerified();
    EventMember::claimFor($newcomer);
    $this->actingAs($newcomer)->patchJson("/api/events/{$event->id}", ['name' => 'Renamed by editor'])->assertOk();
});

// ------------------------------------------------------------------ paid public voting

it('sells votes through Paystack and counts them once paid', function () {
    Mail::fake();
    $event = gfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 50]);
    [, $a] = gfBallot($event);
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/pv']]),
    ]);

    // ₦50 minimum payment.
    $this->postJson("/api/public/events/{$event->link_token}/paid-votes", ['candidate_id' => $a->id, 'quantity' => 1, 'email' => 'fan@example.com'])
        ->assertOk()->assertJsonPath('authorization_url', 'https://checkout.paystack.com/pv');
    $purchase = VotePurchase::first();
    expect($purchase->amount_kobo)->toBe(5000)->and($purchase->fee_kobo)->toBe(500)->and($purchase->reference)->toStartWith('PV-');

    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
        'status' => 'success', 'reference' => $purchase->reference, 'amount' => 5000, 'currency' => 'NGN', 'channel' => 'card', 'paid_at' => now()->toIso8601String(),
    ]])]);
    $this->postJson('/api/public/paid-votes/verify', ['reference' => $purchase->reference])->assertOk()->assertJsonPath('status', 'success');
    $this->postJson('/api/public/paid-votes/verify', ['reference' => $purchase->reference])->assertOk(); // idempotent

    expect($a->fresh()->votes)->toBe(1);
    Mail::assertSent(VotePurchaseReceipt::class, 1);

    // The reference doubles as a receipt on the results page.
    $this->postJson("/api/public/events/{$event->link_token}/receipt", ['receipt' => $purchase->reference])
        ->assertOk()->assertJsonPath('valid', true)->assertJsonPath('votes', 1);

    // Organiser's earnings: gross, 10% fee, net.
    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/earnings")->assertOk()
        ->assertJsonPath('gross_naira', 50)->assertJsonPath('fee_naira', 5)->assertJsonPath('net_naira', 45);
});

it('does not count votes paid for after voting closed', function () {
    $event = gfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 100, 'ends_at' => now()->subMinute()]);
    [, $a] = gfBallot($event);
    $purchase = VotePurchase::create(['event_id' => $event->id, 'candidate_id' => $a->id, 'candidate_name' => 'Ada', 'quantity' => 2,
        'amount_kobo' => 20000, 'reference' => 'PV-LATE1', 'email' => 'fan@example.com']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
        'status' => 'success', 'reference' => 'PV-LATE1', 'amount' => 20000, 'currency' => 'NGN', 'paid_at' => now()->toIso8601String(),
    ]])]);

    $this->postJson('/api/public/paid-votes/verify', ['reference' => 'PV-LATE1'])->assertOk()->assertJsonPath('status', 'late');
    expect($a->fresh()->votes)->toBe(0)->and($purchase->fresh()->status)->toBe('late');
});

it('settles paid votes from the signed webhook', function () {
    $event = gfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 100]);
    [, $a] = gfBallot($event);
    VotePurchase::create(['event_id' => $event->id, 'candidate_id' => $a->id, 'candidate_name' => 'Ada', 'quantity' => 3,
        'amount_kobo' => 30000, 'reference' => 'PV-HOOK1', 'email' => 'fan@example.com']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
        'status' => 'success', 'reference' => 'PV-HOOK1', 'amount' => 30000, 'currency' => 'NGN',
    ]])]);
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'PV-HOOK1']]);

    $this->call('POST', '/api/paystack/webhook', [], [], [], [
        'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret'), 'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();
    expect($a->fresh()->votes)->toBe(3);
});

it('keeps code voting and paid voting apart', function () {
    $event = gfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 50]);
    gfBallot($event);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 5])->assertStatus(422);
    $this->postJson("/api/public/events/{$event->link_token}/vote", ['code' => 'X', 'choices' => [['category_id' => 1, 'candidate_id' => 1]]])
        ->assertStatus(422);
    $this->getJson("/api/public/events/{$event->link_token}")->assertOk()
        ->assertJsonPath('voting_mode', 'paid')->assertJsonPath('vote_price_naira', 50);
});

// ------------------------------------------------------------------ reminders, WhatsApp, branding

it('sends reminders only to voters who have not voted (Pro)', function () {
    Bus::fake();
    $event = gfEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'email' => 'a@example.com']);
    Voter::create(['event_id' => $event->id, 'code' => 'BBBB2222', 'email' => 'b@example.com', 'used_at' => now()]);
    $event->user->markEmailAsVerified();

    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/remind", ['channel' => 'email'])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'messaging');

    gfPro($event);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/remind", ['channel' => 'email'])
        ->assertOk()->assertJsonPath('queued', 1);
    Bus::assertDispatchedAfterResponse(SendVoterInvitation::class, fn ($job) => $job->kind === 'reminder');
});

it('sends a scheduled reminder once, when it is due', function () {
    Bus::fake();
    $event = gfPro(gfEvent());
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'email' => 'a@example.com']);

    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['reminder_at' => now()->addHour()->toIso8601String(), 'reminder_channel' => 'email'])
        ->assertOk();
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['reminder_at' => now()->addDays(3)->toIso8601String()])
        ->assertStatus(422); // after voting closes

    $this->travel(61)->minutes();
    $this->artisan('ballotify:send-reminders')->assertSuccessful();
    $this->artisan('ballotify:send-reminders')->assertSuccessful();
    Bus::assertDispatchedAfterResponseTimes(SendVoterInvitation::class, 1);
    expect($event->fresh()->reminder_sent_at)->not->toBeNull();
});

it('records codes shared on WhatsApp as sent', function () {
    $event = gfEvent();
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'phone' => '+2348031234567']);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/{$voter->id}/shared", ['channel' => 'whatsapp'])
        ->assertOk()->assertJsonPath('invited_via', 'whatsapp');
    expect($voter->fresh()->invited_at)->not->toBeNull();
});

it('lets Pro events use their own colour on the ballot', function () {
    $event = gfEvent();
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/branding", ['brand_color' => '#123abc'])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'branding');

    gfPro($event);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/branding", ['brand_color' => 'red'])->assertStatus(422);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/branding", ['brand_color' => '#123abc'])->assertOk();
    $this->getJson("/api/public/events/{$event->link_token}")->assertJsonPath('brand.color', '#123ABC')->assertJsonPath('branding', false);
});

// ------------------------------------------------------------------ results timeline, receipts, admin

it('includes votes over time in the results', function () {
    $event = gfEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'used_at' => now()->subMinutes(30)]);
    Voter::create(['event_id' => $event->id, 'code' => 'BBBB2222', 'used_at' => now()->subMinutes(5)]);

    $timeline = $this->actingAs($event->user)->getJson("/api/events/{$event->id}/results")->assertOk()->json('timeline');
    expect(array_sum(array_column($timeline['points'], 'votes')))->toBe(2)
        ->and($timeline['bucket_minutes'])->toBeGreaterThan(0);
});

it('emails a receipt for a Pro upgrade and lists it for the organiser', function () {
    Mail::fake();
    $event = gfEvent();
    $payment = Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'event_name' => 'Awards', 'reference' => 'BLT-R1', 'amount_kobo' => 1_000_000, 'currency' => 'NGN']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
        'status' => 'success', 'reference' => 'BLT-R1', 'amount' => 1_000_000, 'currency' => 'NGN', 'channel' => 'card',
    ]])]);

    $this->actingAs($event->user)->postJson('/api/billing/verify', ['reference' => 'BLT-R1'])->assertOk();
    Mail::assertSent(PaymentReceipt::class);
    $this->actingAs($event->user)->getJson('/api/billing/payments')->assertOk()->assertJsonPath('0.reference', 'BLT-R1');
    $this->actingAs($event->user)->getJson('/api/billing/payments/BLT-R1')->assertOk()->assertJsonPath('amount_naira', 10000);
    $this->actingAs(User::factory()->create())->getJson('/api/billing/payments/BLT-R1')->assertNotFound();
});

it('shows the admin system health and the payouts owed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->getJson('/api/admin/health')->assertOk()
        ->assertJsonStructure(['queue' => ['driver', 'failed'], 'scheduler' => ['running'], 'delivery_errors', 'failed_jobs']);

    $event = gfEvent(['voting_mode' => 'paid', 'vote_price_naira' => 100]);
    VotePurchase::create(['event_id' => $event->id, 'candidate_name' => 'Ada', 'quantity' => 10, 'amount_kobo' => 100000, 'fee_kobo' => 10000,
        'reference' => 'PV-P1', 'email' => 'f@example.com', 'status' => 'success', 'paid_at' => now()]);

    $this->actingAs($admin)->getJson('/api/admin/payouts')->assertOk()->assertJsonPath('0.balance_naira', 900);
    $this->actingAs($admin)->postJson("/api/admin/events/{$event->id}/payouts", ['amount_naira' => 400])->assertCreated();
    $this->actingAs($admin)->getJson('/api/admin/payouts')->assertJsonPath('0.balance_naira', 500);
    $this->actingAs($event->user)->getJson('/api/admin/payouts')->assertNotFound();
});
