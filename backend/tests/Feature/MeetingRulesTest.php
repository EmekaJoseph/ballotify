<?php

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\RankedBallot;
use App\Models\User;
use App\Models\Voter;
use App\Support\RankedChoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['ballotify.billing.enabled' => true, 'services.ussd.webhook_secret' => 'ussd-secret']);
});

/** A Pro event (not open yet) with one "Chair" category: candidates A and B. */
function mrEvent(array $attrs = [], bool $pro = true): array
{
    $event = Event::create(array_merge([
        'user_id' => User::factory()->create()->id, 'name' => 'AGM', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'results_visibility' => 'live',
    ], $attrs));
    if ($pro) {
        $event->forceFill(['plan' => 'pro', 'pro_tier' => 'standard'])->save();
    }
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Chair']);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'A']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'B']);
    return [$event->fresh(), $cat, $a, $b];
}

function mrOpen(Event $event): void
{
    $event->update(['starts_at' => now()->subHour()]);
}

function mrVote($test, Event $event, string $code, array $choices)
{
    return $test->postJson("/api/public/events/{$event->link_token}/vote", ['code' => $code, 'choices' => $choices]);
}

// ------------------------------------------------------------------ settings

it('keeps meeting rules off by default, Pro only, and fixed once voting starts', function () {
    [$event] = mrEvent();
    expect($event->weighted_votes)->toBeFalse()->and($event->proxies_enabled)->toBeFalse()->and($event->quorum_type)->toBeNull();

    [$free] = mrEvent(pro: false);
    $this->actingAs($free->user)->patchJson("/api/events/{$free->id}", ['weighted_votes' => true])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'meeting_rules');
    // Switching off / leaving off is never blocked.
    $this->actingAs($free->user)->patchJson("/api/events/{$free->id}", ['weighted_votes' => false, 'name' => 'AGM 2026'])->assertOk();

    $as = $this->actingAs($event->user);
    $as->patchJson("/api/events/{$event->id}", ['quorum_type' => 'percent'])->assertStatus(422)->assertJsonValidationErrors('quorum_value');
    $as->patchJson("/api/events/{$event->id}", ['quorum_type' => 'percent', 'quorum_value' => 150])->assertStatus(422)->assertJsonValidationErrors('quorum_value');
    $as->patchJson("/api/events/{$event->id}", ['weighted_votes' => true, 'proxies_enabled' => true, 'max_proxies' => 3, 'quorum_type' => 'percent', 'quorum_value' => 50])
        ->assertOk()->assertJsonPath('weighted_votes', true)->assertJsonPath('proxies_enabled', true)
        ->assertJsonPath('max_proxies', 3)->assertJsonPath('quorum_type', 'percent')->assertJsonPath('quorum_value', 50)
        ->assertJsonPath('limits.meeting_rules', true);
    expect(AuditLog::where('event_id', $event->id)->where('action', 'event.rules')->pluck('description')->all())
        ->toContain('Weighted votes switched on', 'Proxy voting switched on', 'Quorum set: 50% of members (by weight)');

    // Removing the quorum clears its value.
    $as->patchJson("/api/events/{$event->id}", ['quorum_type' => null])->assertOk()->assertJsonPath('quorum_value', null);

    Voter::create(['event_id' => $event->id, 'code' => 'VOTE0001']);
    mrOpen($event);
    Voter::where('event_id', $event->id)->update(['used_at' => now()]);
    $as->patchJson("/api/events/{$event->id}", ['weighted_votes' => false])->assertStatus(422)->assertJsonValidationErrors('meeting_rules');
    $as->patchJson("/api/events/{$event->id}", ['quorum_type' => 'count', 'quorum_value' => 5])->assertStatus(422);
    // The proxy limit only affects new proxies, so it may still change.
    $as->patchJson("/api/events/{$event->id}", ['max_proxies' => 5])->assertOk();
});

it('rejects meeting rules on paid voting', function () {
    [$event] = mrEvent(['voting_mode' => 'paid', 'vote_price_naira' => 100]);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['proxies_enabled' => true])
        ->assertStatus(422)->assertJsonValidationErrors('meeting_rules');
});

// ------------------------------------------------------------------ weighted votes

it('counts each ballot by the voter’s weight, and locks weights once voting starts', function () {
    [$event, $cat, $a, $b] = mrEvent();
    $as = $this->actingAs($event->user);
    $as->patchJson("/api/events/{$event->id}", ['weighted_votes' => true])->assertOk();

    $big = $as->postJson("/api/events/{$event->id}/voters", ['name' => 'Big Holder', 'code' => 'BIGV0001', 'weight' => 60])
        ->assertCreated()->assertJsonPath('weight', 60)->json();
    $as->postJson("/api/events/{$event->id}/voters/import", ['voters' => [
        ['name' => 'Small 1', 'weight' => '25'], ['name' => 'Small 2', 'weight' => '1,000'], ['name' => 'Small 3'],
    ]])->assertCreated();
    expect(Voter::where('name', 'Small 2')->value('weight'))->toBe(1000)->and(Voter::where('name', 'Small 3')->value('weight'))->toBe(1);
    $as->postJson("/api/events/{$event->id}/voters/import", ['voters' => [['name' => 'Bad', 'weight' => '2.5']]])
        ->assertStatus(422)->assertJsonValidationErrors('voters.0.weight');
    $as->patchJson("/api/events/{$event->id}/voters/{$big['id']}", ['weight' => 40])->assertOk()->assertJsonPath('weight', 40);

    mrOpen($event);
    $t = $event->link_token;
    $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'BIGV0001'])->assertOk()->assertJsonPath('weight', 40)->assertJsonPath('proxy_for', []);
    mrVote($this, $event, 'BIGV0001', [['category_id' => $cat->id, 'candidate_id' => $a->id]])->assertOk();
    mrVote($this, $event, Voter::where('name', 'Small 1')->value('code'), [['category_id' => $cat->id, 'candidate_id' => $b->id]])->assertOk();
    mrVote($this, $event, Voter::where('name', 'Small 3')->value('code'), [['category_id' => $cat->id, 'candidate_id' => $b->id]])->assertOk();

    // 40 for A against 26 for B: one big holder outweighs two members.
    expect($a->fresh()->votes)->toBe(40)->and($b->fresh()->votes)->toBe(26)->and($cat->fresh()->ballots)->toBe(66);
    $res = $this->getJson("/api/public/events/{$t}/results")->assertOk();
    $res->assertJsonPath('categories.0.winners', [$a->id])
        ->assertJsonPath('rules.weighted', true)->assertJsonPath('rules.total_weight', 1066)->assertJsonPath('rules.voted_weight', 66);

    $as->patchJson("/api/events/{$event->id}/voters/{$big['id']}", ['weight' => 1])->assertStatus(422)->assertJsonValidationErrors('weight');
});

it('ignores weights when weighted votes are off', function () {
    [$event, $cat, $a] = mrEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'BIGV0001', 'weight' => 500]);
    mrOpen($event);
    mrVote($this, $event, 'BIGV0001', [['category_id' => $cat->id, 'candidate_id' => $a->id]])->assertOk();
    expect($a->fresh()->votes)->toBe(1);
});

it('weights ranked ballots and motions too', function () {
    // The count itself: 1 ballot of weight 5 beats 4 ballots of weight 1.
    $count = RankedChoice::count([1, 2, 3], [[1, 2], [2, 1], [2, 1], [3, 2], [3, 2]], [5, 1, 1, 1, 1]);
    expect($count['winners'])->toBe([1])->and($count['rounds'][0]['counts'])->toBe([1 => 5, 2 => 2, 3 => 2]);

    [$event] = mrEvent();
    $event->update(['weighted_votes' => true]);
    $ranked = Category::create(['event_id' => $event->id, 'name' => 'Treasurer', 'method' => 'ranked', 'max_choices' => null]);
    $x = Candidate::create(['event_id' => $event->id, 'category_id' => $ranked->id, 'name' => 'X']);
    $y = Candidate::create(['event_id' => $event->id, 'category_id' => $ranked->id, 'name' => 'Y']);
    $motion = $this->actingAs($event->user)->postJson("/api/events/{$event->id}/categories", ['name' => 'Approve accounts', 'method' => 'motion', 'pass_rule' => 'two_thirds'])->json();
    $opt = Candidate::where('category_id', $motion['id'])->get()->keyBy('motion_choice');

    Voter::create(['event_id' => $event->id, 'code' => 'HEAVY001', 'weight' => 3]);
    Voter::create(['event_id' => $event->id, 'code' => 'LIGHT001', 'weight' => 1]);
    mrOpen($event);
    mrVote($this, $event, 'HEAVY001', [
        ['category_id' => $ranked->id, 'candidate_id' => $x->id, 'rank' => 1], ['category_id' => $ranked->id, 'candidate_id' => $y->id, 'rank' => 2],
        ['category_id' => $motion['id'], 'candidate_id' => $opt['yes']->id],
    ])->assertOk();
    mrVote($this, $event, 'LIGHT001', [
        ['category_id' => $ranked->id, 'candidate_id' => $y->id, 'rank' => 1],
        ['category_id' => $motion['id'], 'candidate_id' => $opt['no']->id],
    ])->assertOk();

    expect(RankedBallot::where('category_id', $ranked->id)->pluck('weight')->sort()->values()->all())->toBe([1, 3]);
    $res = $this->getJson("/api/public/events/{$event->link_token}/results")->json('categories');
    $byName = collect($res)->keyBy('name');
    expect($byName['Treasurer']['winners'])->toBe([$x->id])
        // Yes 3 / No 1 = 75%: passes two thirds.
        ->and($byName['Approve accounts']['motion']['yes'])->toBe(3)->and($byName['Approve accounts']['motion']['passed'])->toBeTrue();
});

// ------------------------------------------------------------------ proxies

it('lets a proxy holder vote for absent members, who then can’t vote themselves', function () {
    [$event, $cat, $a, $b] = mrEvent();
    $as = $this->actingAs($event->user);
    $holder = Voter::create(['event_id' => $event->id, 'name' => 'Chidi', 'code' => 'HOLD0001']);
    $p1 = Voter::create(['event_id' => $event->id, 'name' => 'Amaka', 'code' => 'PRIN0001']);
    $p2 = Voter::create(['event_id' => $event->id, 'name' => 'Tunde', 'code' => 'PRIN0002']);
    $p3 = Voter::create(['event_id' => $event->id, 'name' => 'Musa', 'code' => 'PRIN0003']);

    // Off by default: no proxies.
    $as->patchJson("/api/events/{$event->id}/voters/{$p1->id}", ['proxy_voter_id' => $holder->id])->assertStatus(422)->assertJsonValidationErrors('proxy_voter_id');
    $as->patchJson("/api/events/{$event->id}", ['proxies_enabled' => true, 'max_proxies' => 2])->assertOk();

    $as->patchJson("/api/events/{$event->id}/voters/{$p1->id}", ['proxy_voter_id' => $p1->id])->assertStatus(422); // self
    $as->patchJson("/api/events/{$event->id}/voters/{$p1->id}", ['proxy_voter_id' => $holder->id])->assertOk()->assertJsonPath('proxy_voter_id', $holder->id);
    $as->patchJson("/api/events/{$event->id}/voters/{$p2->id}", ['proxy_voter_id' => $holder->id])->assertOk();
    // Limit of 2 per holder.
    $as->patchJson("/api/events/{$event->id}/voters/{$p3->id}", ['proxy_voter_id' => $holder->id])->assertStatus(422)
        ->assertJsonPath('errors.proxy_voter_id.0', 'Chidi already holds 2 proxies, the most allowed.');
    // No chains: a principal can't hold others' proxies, and a holder can't give theirs away.
    $as->patchJson("/api/events/{$event->id}/voters/{$p3->id}", ['proxy_voter_id' => $p1->id])->assertStatus(422);
    $as->patchJson("/api/events/{$event->id}/voters/{$holder->id}", ['proxy_voter_id' => $p3->id])->assertStatus(422);
    // Another event's voter can't be chosen.
    [$other] = mrEvent();
    $stranger = Voter::create(['event_id' => $other->id, 'code' => 'STRA0001']);
    $as->patchJson("/api/events/{$event->id}/voters/{$p3->id}", ['proxy_voter_id' => $stranger->id])->assertStatus(422);
    expect(AuditLog::where('event_id', $event->id)->where('action', 'voters.proxy')->pluck('description')->all())->toContain('Chidi will vote for Amaka (proxy)');

    mrOpen($event);
    $t = $event->link_token;
    // The member whose proxy is held can't vote.
    $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'PRIN0001'])->assertStatus(422)->assertJsonPath('proxy', true)
        ->assertJsonPath('message', 'Your vote is being cast by your proxy, Chidi. Ask the organiser if this is wrong.');
    mrVote($this, $event, 'PRIN0001', [['category_id' => $cat->id, 'candidate_id' => $b->id]])->assertStatus(422);

    $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'HOLD0001'])->assertOk()
        ->assertJsonPath('weight', 3)->assertJsonPath('proxy_for', ['Amaka', 'Tunde']);
    mrVote($this, $event, 'HOLD0001', [['category_id' => $cat->id, 'candidate_id' => $a->id]])->assertOk();
    mrVote($this, $event, 'PRIN0003', [['category_id' => $cat->id, 'candidate_id' => $b->id]])->assertOk();

    expect($a->fresh()->votes)->toBe(3)->and($b->fresh()->votes)->toBe(1)
        ->and(Voter::where('event_id', $event->id)->whereNotNull('used_at')->count())->toBe(4);
    // Once voted, a proxy can't be moved.
    $as->patchJson("/api/events/{$event->id}/voters/{$p1->id}", ['proxy_voter_id' => null])->assertStatus(422);
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules.proxies', true)->assertJsonPath('rules.proxy_votes', 2);
});

it('doesn’t send codes or reminders to members whose proxy votes for them', function () {
    \Illuminate\Support\Facades\Queue::fake();
    [$event] = mrEvent(['proxies_enabled' => true]);
    $holder = Voter::create(['event_id' => $event->id, 'code' => 'HOLD0001', 'email' => 'holder@example.com']);
    Voter::create(['event_id' => $event->id, 'code' => 'PRIN0001', 'email' => 'absent@example.com', 'proxy_voter_id' => $holder->id]);
    $event->user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'pending'])
        ->assertOk()->assertJsonPath('queued', 1);
});

it('frees members when proxies are switched off or the holder is removed', function () {
    [$event] = mrEvent(['proxies_enabled' => true]);
    $holder = Voter::create(['event_id' => $event->id, 'name' => 'Chidi', 'code' => 'HOLD0001']);
    $p1 = Voter::create(['event_id' => $event->id, 'code' => 'PRIN0001', 'proxy_voter_id' => $holder->id]);
    $as = $this->actingAs($event->user);

    $as->deleteJson("/api/events/{$event->id}/voters/{$holder->id}")->assertNoContent();
    expect($p1->fresh()->proxy_voter_id)->toBeNull();

    $h2 = Voter::create(['event_id' => $event->id, 'code' => 'HOLD0002']);
    $p1->update(['proxy_voter_id' => $h2->id]);
    $as->patchJson("/api/events/{$event->id}", ['proxies_enabled' => false])->assertOk();
    expect($p1->fresh()->proxy_voter_id)->toBeNull();
});

it('counts a proxy only in the categories the absent member may vote in', function () {
    [$event, $cat, $a] = mrEvent(['proxies_enabled' => true]);
    $law = Category::create(['event_id' => $event->id, 'name' => 'Law Rep', 'groups' => ['Law']]);
    $l = Candidate::create(['event_id' => $event->id, 'category_id' => $law->id, 'name' => 'L']);
    $holder = Voter::create(['event_id' => $event->id, 'code' => 'HOLD0001', 'groups' => ['Engineering']]);
    Voter::create(['event_id' => $event->id, 'name' => 'Lawyer', 'code' => 'PRIN0001', 'groups' => ['Law'], 'proxy_voter_id' => $holder->id]);
    mrOpen($event);

    // The engineer can vote in Law Rep only on the lawyer's behalf.
    $this->postJson("/api/public/events/{$event->link_token}/code/validate", ['code' => 'HOLD0001'])->assertOk()
        ->assertJsonPath('categories', [$cat->id, $law->id]);
    mrVote($this, $event, 'HOLD0001', [['category_id' => $cat->id, 'candidate_id' => $a->id], ['category_id' => $law->id, 'candidate_id' => $l->id]])->assertOk();
    expect($a->fresh()->votes)->toBe(2)->and($l->fresh()->votes)->toBe(1)->and($law->fresh()->ballots)->toBe(1);
});

it('takes proxies over USSD', function () {
    [$event, $cat, $a] = mrEvent(['proxies_enabled' => true]);
    $event->forceFill(['ussd_enabled' => true, 'ussd_code' => '123456'])->save();
    $holder = Voter::create(['event_id' => $event->id, 'code' => '1111111111']);
    Voter::create(['event_id' => $event->id, 'code' => '2222222222', 'proxy_voter_id' => $holder->id]);
    mrOpen($event);
    $ussd = fn (string $text) => $this->post('/api/ussd/ussd-secret', ['text' => $text])->getContent();

    expect($ussd('123456*2222222222'))->toContain('END Your vote is being cast by your proxy');
    $ussd('123456*1111111111*1*1');
    expect($a->fresh()->votes)->toBe(2);
});

// ------------------------------------------------------------------ quorum

it('reports quorum progress by members, or by weight when votes are weighted', function () {
    [$event, $cat, $a] = mrEvent(['quorum_type' => 'percent', 'quorum_value' => 50]);
    foreach (range(1, 4) as $i) {
        Voter::create(['event_id' => $event->id, 'code' => "VOTE000{$i}", 'weight' => $i === 1 ? 7 : 1]);
    }
    mrOpen($event);
    $t = $event->link_token;
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules.quorum', [
        'type' => 'percent', 'value' => 50, 'basis' => 'voters', 'total' => 4, 'required' => 2, 'achieved' => 0, 'met' => false,
    ]);
    mrVote($this, $event, 'VOTE0002', [['category_id' => $cat->id, 'candidate_id' => $a->id]])->assertOk();
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules.quorum.achieved', 1)->assertJsonPath('rules.quorum.met', false);
    mrVote($this, $event, 'VOTE0003', [['category_id' => $cat->id, 'candidate_id' => $a->id]])->assertOk();
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules.quorum.met', true);

    // By weight: 2 of 10 voted, 50% needs 5.
    $event->forceFill(['weighted_votes' => true])->save();
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules.quorum.basis', 'weight')
        ->assertJsonPath('rules.quorum.total', 10)->assertJsonPath('rules.quorum.required', 5)->assertJsonPath('rules.quorum.met', false);

    // A fixed number.
    $event->forceFill(['weighted_votes' => false, 'quorum_type' => 'count', 'quorum_value' => 3])->save();
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules.quorum.required', 3)->assertJsonPath('rules.quorum.met', false);

    // No rules: nothing reported.
    $event->forceFill(['quorum_type' => null, 'quorum_value' => null])->save();
    $this->getJson("/api/public/events/{$t}/results")->assertJsonPath('rules', null);
});

it('records the rules on the results certificate', function () {
    [$event, $cat, $a] = mrEvent(['quorum_type' => 'count', 'quorum_value' => 1]);
    Voter::create(['event_id' => $event->id, 'code' => 'VOTE0001']);
    mrOpen($event);
    mrVote($this, $event, 'VOTE0001', [['category_id' => $cat->id, 'candidate_id' => $a->id]])->assertOk();
    $event->update(['ends_at' => now()->subMinute()]);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/certificates")->assertCreated();
    expect(\App\Models\ResultCertificate::latest('id')->first()->payload['rules']['quorum']['met'])->toBeTrue();
});
