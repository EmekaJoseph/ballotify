<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\RankedBallot;
use App\Models\User;
use App\Models\Voter;
use App\Support\RankedChoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['ballotify.billing.enabled' => true, 'services.ussd.webhook_secret' => 'ussd-secret']);
});

function rcEvent(array $attrs = []): Event
{
    return Event::create(array_merge([
        'user_id' => User::factory()->create()->id, 'name' => 'Union', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'results_visibility' => 'live',
    ], $attrs));
}

/** A ranked category with candidates A, B, C and $n voter codes V1…Vn. */
function rcRanked(Event $event, int $voters = 9): array
{
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Chair', 'method' => 'ranked', 'max_choices' => null]);
    $c = collect(['A', 'B', 'C'])->mapWithKeys(fn ($n) => [$n => Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => $n])]);
    foreach (range(1, $voters) as $i) {
        Voter::create(['event_id' => $event->id, 'code' => 'VOTE' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)]);
    }
    return [$cat, $c];
}

function rcVote($test, Event $event, Category $cat, string $code, array $order)
{
    return $test->postJson("/api/public/events/{$event->link_token}/vote", ['code' => $code, 'choices' => array_map(
        fn ($cand, $i) => ['category_id' => $cat->id, 'candidate_id' => $cand->id, 'rank' => $i + 1], $order, array_keys($order))]);
}

// ------------------------------------------------------------------ the count itself

it('counts in rounds: a majority wins at once, otherwise the last is eliminated and votes move on', function () {
    // Outright majority.
    expect(RankedChoice::count([1, 2, 3], [[1], [1, 2], [2], [1, 3]])['winners'])->toBe([1]);

    // 1 leads on first choices (4) but 3's voters prefer 2: 2 wins in round 2.
    $r = RankedChoice::count([1, 2, 3], array_merge(array_fill(0, 4, [1, 3]), array_fill(0, 3, [2, 3]), array_fill(0, 2, [3, 2])));
    expect($r['winners'])->toBe([2])
        ->and($r['rounds'])->toHaveCount(2)
        ->and($r['rounds'][0]['eliminated'])->toBe([3])
        ->and($r['rounds'][1]['counts'])->toBe([2 => 5, 1 => 4])
        ->and($r['order'])->toBe([2, 1, 3]);

    // Ballots that run out of choices are exhausted; the majority is of continuing ballots.
    $r = RankedChoice::count([1, 2, 3], [[1], [1], [2], [2], [3]]);
    expect($r['rounds'][0]['eliminated'])->toBe([3])
        ->and($r['rounds'][1]['exhausted'])->toBe(1)
        ->and($r['winners'])->toBe([1, 2]); // 2–2 with nobody else left: a shared win

    // Tie for last broken by the earlier round.
    $r = RankedChoice::count([1, 2, 3, 4], array_merge(
        array_fill(0, 5, [1]), array_fill(0, 4, [2]), array_fill(0, 2, [3, 2]), array_fill(0, 1, [4, 3]),
    ));
    expect($r['rounds'][0]['eliminated'])->toBe([4])          // round 1: 4 has fewest
        ->and($r['rounds'][1]['eliminated'])->toBe([3]);       // round 2: 3 (with 4's vote) still last

    // A real tie for last (2 and 3 both on 4 in round 2) is broken by round 1, where 2 had fewer.
    $r = RankedChoice::count([1, 2, 3, 4], array_merge(
        array_fill(0, 6, [1]), array_fill(0, 3, [2]), array_fill(0, 4, [3]), [[4, 2]],
    ));
    expect($r['rounds'][1]['counts'])->toBe([1 => 6, 2 => 4, 3 => 4])
        ->and($r['rounds'][1]['eliminated'])->toBe([2]);

    // Tied for last in every round: both go out together.
    $r = RankedChoice::count([1, 2, 3, 4], array_merge(array_fill(0, 3, [1]), array_fill(0, 2, [4]), [[2]], [[3]]));
    expect($r['rounds'][0]['eliminated'])->toEqualCanonicalizing([2, 3])
        ->and($r['rounds'][1]['exhausted'])->toBe(2)
        ->and($r['winners'])->toBe([1]); // 3 of the 5 continuing ballots

    // No votes: no winner.
    expect(RankedChoice::count([1, 2], [])['winners'])->toBe([]);
});

// ------------------------------------------------------------------ voting and results

it('runs a ranked-choice category end to end, keeping ballots anonymous', function () {
    $event = rcEvent();
    [$cat, $c] = rcRanked($event);
    $n = 0;
    foreach ([[4, ['A', 'C']], [3, ['B', 'C']], [2, ['C', 'B']]] as [$times, $order]) {
        foreach (range(1, $times) as $_) {
            $n++;
            rcVote($this, $event, $cat, 'VOTE' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), array_map(fn ($x) => $c[$x], $order))->assertOk();
        }
    }

    // Totals hold first choices only; the rounds decide.
    expect($c['A']->fresh()->votes)->toBe(4)->and($c['B']->fresh()->votes)->toBe(3)->and($c['C']->fresh()->votes)->toBe(2)
        ->and($cat->fresh()->ballots)->toBe(9);

    $res = $this->getJson("/api/public/events/{$event->link_token}/results")->assertOk()->json('categories.0');
    expect($res['method'])->toBe('ranked')
        ->and($res['winners'])->toBe([$c['B']->id])
        ->and($res['leader']['name'])->toBe('B')
        ->and(array_column($res['candidates'], 'name'))->toBe(['B', 'A', 'C'])
        ->and($res['rounds'])->toHaveCount(2)
        ->and($res['rounds'][0]['eliminated'])->toBe([$c['C']->id])
        ->and($res['ballots'])->toBe(9);

    // The stored rankings say nothing about who voted or when (weight = votes carried, 1 unless weighted/proxy).
    expect(Schema::getColumnListing('ranked_ballots'))->toEqualCanonicalizing(['id', 'category_id', 'ranking', 'weight'])
        ->and(RankedBallot::count())->toBe(9)
        ->and(RankedBallot::pluck('id')->every(fn ($id) => Str::isUuid($id) && $id[14] === '4'))->toBeTrue(); // v4: random, not time-ordered
});

it('enforces the ranking limit and rejects duplicate tricks', function () {
    $event = rcEvent();
    [$cat, $c] = rcRanked($event, 3);
    $cat->update(['max_choices' => 2]);

    rcVote($this, $event, $cat, 'VOTE0001', [$c['A'], $c['B'], $c['C']])->assertStatus(422);
    // Listing a candidate twice counts once.
    rcVote($this, $event, $cat, 'VOTE0001', [$c['A'], $c['A'], $c['B']])->assertOk();
    expect(RankedBallot::first()->ranking)->toBe([$c['A']->id, $c['B']->id]);
    // Without ranks, the listed order is the ranking.
    $this->postJson("/api/public/events/{$event->link_token}/vote", ['code' => 'VOTE0002', 'choices' => [
        ['category_id' => $cat->id, 'candidate_id' => $c['C']->id], ['category_id' => $cat->id, 'candidate_id' => $c['A']->id],
    ]])->assertOk();
    expect($c['C']->fresh()->votes)->toBe(1);
});

it('lets organisers choose ranked choice before voting, but not for paid voting or once voting started', function () {
    $event = rcEvent(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    $as = $this->actingAs($event->user);
    $cat = $as->postJson("/api/events/{$event->id}/categories", ['name' => 'Chair', 'method' => 'ranked'])->assertCreated()
        ->assertJsonPath('method', 'ranked')->assertJsonPath('max_choices', null)->json();

    $as->patchJson("/api/events/{$event->id}/categories/{$cat['id']}", ['method' => 'plurality', 'max_choices' => 1])->assertOk()->assertJsonPath('method', 'plurality');
    $as->patchJson("/api/events/{$event->id}/categories/{$cat['id']}", ['method' => 'nonsense'])->assertStatus(422);

    $paid = rcEvent(['user_id' => $event->user_id, 'voting_mode' => 'paid', 'vote_price_naira' => 50]);
    $this->actingAs($event->user)->postJson("/api/events/{$paid->id}/categories", ['name' => 'Best', 'method' => 'ranked'])->assertStatus(422);

    // Locked once voting has started.
    $open = rcEvent(['user_id' => $event->user_id]);
    [$rc, $c] = rcRanked($open, 1);
    rcVote($this, $open, $rc, 'VOTE0001', [$c['A']])->assertOk();
    $this->actingAs($event->user)->patchJson("/api/events/{$open->id}/categories/{$rc->id}", ['method' => 'plurality'])->assertStatus(409);
});

it('certifies ranked results by the rounds, not by first choices', function () {
    $event = rcEvent();
    [$cat, $c] = rcRanked($event);
    $n = 0;
    foreach ([[4, ['A', 'C']], [3, ['B', 'C']], [2, ['C', 'B']]] as [$times, $order]) {
        foreach (range(1, $times) as $_) {
            rcVote($this, $event, $cat, 'VOTE' . str_pad((string) ++$n, 4, '0', STR_PAD_LEFT), array_map(fn ($x) => $c[$x], $order))->assertOk();
        }
    }
    $event->update(['ends_at' => now()->subMinute()]);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/certificates")->assertCreated()
        ->assertJsonPath('payload.categories.0.winners', ['B'])
        ->assertJsonPath('payload.categories.0.method', 'ranked')
        ->assertJsonPath('payload.categories.0.rounds', 2);
});

it('lets USSD voters rank with the keypad', function () {
    $event = rcEvent(['plan' => 'pro']);
    $event->forceFill(['plan' => 'pro'])->save();
    [$cat, $c] = rcRanked($event, 0);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['ussd_enabled' => true])->assertOk();
    $event->refresh();
    $code = $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 1])->json('voters.0.code');
    $ussd = fn (string $text) => $this->post('/api/ussd/ussd-secret', ['text' => "{$event->ussd_code}*{$code}" . ($text === '' ? '' : "*{$text}")])->getContent();

    expect($ussd(''))->toContain("Chair (1/1)\nYour 1st choice:\n1. A\n2. B\n3. C\n0. Skip")
        ->and($ussd('3'))->toContain("Your 2nd choice:\n1. A\n2. B\n0. Done")   // C taken, list renumbered
        ->and($ussd('3*1'))->toContain("Your 3rd choice:\n1. B\n0. Done")
        ->and($ussd('3*1*0'))->toContain("Confirm your vote:\nChair: C>A");

    expect($ussd('3*1*0*1'))->toStartWith('END Thank you');
    expect(RankedBallot::first()->ranking)->toBe([$c['C']->id, $c['A']->id])
        ->and($c['C']->fresh()->votes)->toBe(1);
});

it('reports approval categories as a share of voters', function () {
    $event = rcEvent();
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Board', 'max_choices' => null]);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Bola']);
    foreach ([[$a, $b], [$a], [$a, $b], [$b]] as $i => $picks) {
        Voter::create(['event_id' => $event->id, 'code' => "APPR000{$i}"]);
        $this->postJson("/api/public/events/{$event->link_token}/vote", ['code' => "APPR000{$i}", 'choices' => array_map(
            fn ($p) => ['category_id' => $cat->id, 'candidate_id' => $p->id], $picks)])->assertOk();
    }
    $res = $this->getJson("/api/public/events/{$event->link_token}/results")->json('categories.0');
    // 4 voters; Ada approved by 3 (75%), Bola by 3: a shared top.
    expect($res['ballots'])->toBe(4)->and($res['method'])->toBe('plurality')
        ->and($res['winners'])->toEqualCanonicalizing([$a->id, $b->id]);
});
