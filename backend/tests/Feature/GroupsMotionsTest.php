<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['ballotify.billing.enabled' => true, 'services.ussd.webhook_secret' => 'ussd-secret']);
});

function gmEvent(array $attrs = []): Event
{
    $event = Event::create(array_merge([
        'user_id' => User::factory()->create()->id, 'name' => 'Union', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'results_visibility' => 'live',
    ], $attrs));
    $event->forceFill(['plan' => 'pro'])->save();
    return $event->fresh();
}

function gmOpen(Event $event): void
{
    $event->update(['starts_at' => now()->subHour()]);
}

// ------------------------------------------------------------------ voter groups

it('shows each voter only the categories for their groups, and enforces it', function () {
    $event = gmEvent();
    $as = $this->actingAs($event->user);
    $pres = $as->postJson("/api/events/{$event->id}/categories", ['name' => 'President'])->json();
    $law = $as->postJson("/api/events/{$event->id}/categories", ['name' => 'Law Rep', 'groups' => [' Law ', 'law']])->assertCreated()
        ->assertJsonPath('groups', ['Law'])->json();
    $p = $as->postJson("/api/events/{$event->id}/candidates", ['category_id' => $pres['id'], 'name' => 'Ada'])->json();
    $l = $as->postJson("/api/events/{$event->id}/candidates", ['category_id' => $law['id'], 'name' => 'Bola'])->json();

    $as->postJson("/api/events/{$event->id}/voters", ['name' => 'Lawyer', 'code' => 'LAWV0001', 'groups' => 'LAW; 300L'])->assertCreated()
        ->assertJsonPath('groups', ['LAW', '300L']);
    $as->postJson("/api/events/{$event->id}/voters/import", ['voters' => [['name' => 'Engineer', 'groups' => 'Engineering|300L']]])->assertCreated();
    $engCode = Voter::where('name', 'Engineer')->value('code');
    expect(Voter::where('name', 'Engineer')->first()->groups)->toBe(['Engineering', '300L']);

    $as->getJson("/api/events/{$event->id}/voter-groups")->assertOk()
        ->assertJsonPath('0.name', '300L')->assertJsonPath('0.voters', 2)
        ->assertJsonFragment(['name' => 'Engineering', 'voters' => 1]);

    gmOpen($event);
    $t = $event->link_token;
    $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'LAWV0001'])->assertOk()->assertJsonPath('categories', [$pres['id'], $law['id']]);
    $this->postJson("/api/public/events/{$t}/code/validate", ['code' => $engCode])->assertOk()->assertJsonPath('categories', [$pres['id']]);

    // The engineer can't sneak a vote into Law Rep.
    $this->postJson("/api/public/events/{$t}/vote", ['code' => $engCode, 'choices' => [
        ['category_id' => $pres['id'], 'candidate_id' => $p['id']], ['category_id' => $law['id'], 'candidate_id' => $l['id']],
    ]])->assertStatus(422)->assertJsonPath('message', 'You can’t vote in “Law Rep”.');
    expect(Voter::where('code', $engCode)->value('used_at'))->toBeNull(); // nothing recorded

    $this->postJson("/api/public/events/{$t}/vote", ['code' => $engCode, 'choices' => [['category_id' => $pres['id'], 'candidate_id' => $p['id']]]])->assertOk();
    $this->postJson("/api/public/events/{$t}/vote", ['code' => 'LAWV0001', 'choices' => [['category_id' => $law['id'], 'candidate_id' => $l['id']]]])->assertOk();

    $res = $this->getJson("/api/public/events/{$t}/results")->json('categories');
    expect($res[1]['eligible'])->toBe(1)->and($res[1]['ballots'])->toBe(1)->and($res[0]['eligible'])->toBeNull();

    // Who can vote is a ballot rule: locked once voting has started.
    $as->patchJson("/api/events/{$event->id}/categories/{$law['id']}", ['groups' => []])->assertStatus(409);
});

it('applies voter groups on USSD too', function () {
    $event = gmEvent();
    $event->forceFill(['ussd_enabled' => true, 'ussd_code' => '123456'])->save();
    $pres = Category::create(['event_id' => $event->id, 'name' => 'President']);
    $law = Category::create(['event_id' => $event->id, 'name' => 'Law Rep', 'groups' => ['Law']]);
    Candidate::create(['event_id' => $event->id, 'category_id' => $pres->id, 'name' => 'Ada']);
    Candidate::create(['event_id' => $event->id, 'category_id' => $law->id, 'name' => 'Bola']);
    Voter::create(['event_id' => $event->id, 'code' => '1111111111', 'groups' => ['Engineering']]);
    gmOpen($event);

    $menu = $this->post('/api/ussd/ussd-secret', ['text' => '123456*1111111111'])->getContent();
    expect($menu)->toContain('President (1/1)')->not->toContain('Law Rep');
});

// ------------------------------------------------------------------ motions

it('runs a motion with Yes / No / Abstain and a pass rule', function () {
    $event = gmEvent();
    $as = $this->actingAs($event->user);
    $m = $as->postJson("/api/events/{$event->id}/categories", [
        'name' => 'Approve the 2026 accounts', 'method' => 'motion', 'pass_rule' => 'two_thirds',
        'description' => "That the audited accounts\nbe approved.",
    ])->assertCreated()->assertJsonPath('method', 'motion')->assertJsonPath('max_choices', 1)->json();
    $options = Candidate::where('category_id', $m['id'])->orderBy('id')->get();
    expect($options->pluck('motion_choice')->all())->toBe(['yes', 'no', 'abstain']);

    // The options are fixed.
    $as->postJson("/api/events/{$event->id}/candidates", ['category_id' => $m['id'], 'name' => 'Maybe'])->assertStatus(422);
    $as->patchJson("/api/events/{$event->id}/candidates/{$options[0]->id}", ['name' => 'Aye'])->assertStatus(422);
    $as->deleteJson("/api/events/{$event->id}/candidates/{$options[2]->id}")->assertStatus(422);
    $as->patchJson("/api/events/{$event->id}/categories/{$m['id']}", ['method' => 'plurality'])->assertStatus(422);
    $as->getJson("/api/events/{$event->id}/candidates/{$options[0]->id}/profile-link")->assertStatus(422);

    // Paid events can't have motions.
    $paid = gmEvent(['user_id' => $event->user_id, 'voting_mode' => 'paid', 'vote_price_naira' => 50]);
    $this->actingAs($event->user)->postJson("/api/events/{$paid->id}/categories", ['name' => 'X', 'method' => 'motion'])->assertStatus(422);

    gmOpen($event);
    $votes = ['yes' => 6, 'no' => 4, 'abstain' => 5]; // 6 of 10 = 60%: not two-thirds
    $n = 0;
    foreach ($votes as $choice => $times) {
        foreach (range(1, $times) as $_) {
            $code = 'MOTN' . str_pad((string) ++$n, 4, '0', STR_PAD_LEFT);
            Voter::create(['event_id' => $event->id, 'code' => $code]);
            $this->postJson("/api/public/events/{$event->link_token}/vote", ['code' => $code, 'choices' => [
                ['category_id' => $m['id'], 'candidate_id' => $options->firstWhere('motion_choice', $choice)->id],
            ]])->assertOk();
        }
    }
    $res = $this->getJson("/api/public/events/{$event->link_token}/results")->json('categories.0');
    expect($res['motion'])->toBe(['yes' => 6, 'no' => 4, 'abstain' => 5, 'rule' => 'two_thirds', 'decided' => true, 'passed' => false])
        ->and($res['winners'])->toBe([])
        ->and($res['description'])->toBe("That the audited accounts\nbe approved.")
        ->and(array_column($res['candidates'], 'motion_choice'))->toBe(['yes', 'no', 'abstain']);

    // The ballot carries the text and the fixed options.
    $ballot = $this->getJson("/api/public/events/{$event->link_token}")->json();
    expect($ballot['categories'][0]['pass_rule'])->toBe('two_thirds')
        ->and(collect($ballot['candidates'])->pluck('motion_choice')->all())->toBe(['yes', 'no', 'abstain']);

    // A simple majority would have passed it: the rule is locked now, so check the maths directly.
    $cat = Category::find($m['id']);
    $cat->pass_rule = 'simple';
    expect(\App\Support\Tally::category($cat, $cat->candidates)['motion']['passed'])->toBeTrue();

    $event->update(['ends_at' => now()->subMinute()]);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/certificates")->assertCreated()
        ->assertJsonPath('payload.categories.0.method', 'motion')
        ->assertJsonPath('payload.categories.0.motion.passed', false);
});

// ------------------------------------------------------------------ campaign cards

it('serves candidate photos through the API for campaign cards', function () {
    $event = gmEvent();
    $cat = Category::create(['event_id' => $event->id, 'name' => 'President']);
    $id = $this->actingAs($event->user)->post("/api/events/{$event->id}/candidates", [
        'category_id' => $cat->id, 'name' => 'Ada', 'image' => UploadedFile::fake()->image('ada.jpg', 200, 200),
    ], ['Accept' => 'application/json'])->assertCreated()->json('id');
    $bare = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Bola']);

    $this->get("/api/public/events/{$event->link_token}/candidates/{$id}/photo")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get("/api/public/events/{$event->link_token}/candidates/{$bare->id}/photo")->assertNotFound();
    $other = gmEvent();
    $this->get("/api/public/events/{$other->link_token}/candidates/{$id}/photo")->assertNotFound();

    @unlink(public_path(Candidate::find($id)->image_path));
});
