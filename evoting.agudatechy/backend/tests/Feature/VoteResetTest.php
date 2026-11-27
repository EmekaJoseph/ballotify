<?php

use App\Models\AuditLog;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\ResultCertificate;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Open codes election, 3 voters (V1, V2, V3), one category with two candidates. */
function resetElection(array $attrs = []): array
{
    $event = Event::create(array_merge([
        'user_id' => User::factory()->create()->id, 'name' => 'Annual Elections', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'results_visibility' => 'live',
    ], $attrs));
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Chairman']);
    $ada = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    $bola = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Bola']);
    foreach (['V1AAAAAA', 'V2AAAAAA', 'V3AAAAAA'] as $code) {
        Voter::create(['event_id' => $event->id, 'code' => $code]);
    }
    return [$event, $cat, $ada, $bola];
}

function castFor($test, Event $event, Category $cat, Candidate $c, string $code)
{
    return $test->postJson("/api/public/events/{$event->link_token}/vote", ['code' => $code, 'choices' => [['category_id' => $cat->id, 'candidate_id' => $c->id]]]);
}

it('resets votes and results after a test run, and keeps a permanent record', function () {
    [$event, $cat, $ada, $bola] = resetElection();
    castFor($this, $event, $cat, $ada, 'V1AAAAAA')->assertOk();
    castFor($this, $event, $cat, $bola, 'V2AAAAAA')->assertOk();
    $url = "/api/events/{$event->id}/reset-votes";
    $as = $this->actingAs($event->user);

    $as->postJson($url, ['confirm_name' => 'Wrong name'])->assertStatus(422)->assertJsonValidationErrors('confirm_name');
    $as->postJson($url, ['confirm_name' => '  annual elections '])->assertOk()->assertJsonPath('erased', 2);

    expect((int) Candidate::sum('votes'))->toBe(0)
        ->and($cat->fresh()->ballots)->toBe(0)
        ->and(BallotReceipt::where('event_id', $event->id)->count())->toBe(0)
        ->and(Voter::where('event_id', $event->id)->whereNotNull('used_at')->count())->toBe(0)
        ->and(Voter::where('event_id', $event->id)->count())->toBe(3); // voters and codes stay

    // The same codes work again, and the ballot is editable again.
    castFor($this, $event, $cat, $bola, 'V1AAAAAA')->assertOk();
    expect($bola->fresh()->votes)->toBe(1);

    // Recorded for good, and shown with the results.
    expect(AuditLog::where('event_id', $event->id)->where('action', 'votes.reset')->count())->toBe(1);
    $as->getJson("/api/events/{$event->id}/results")->assertOk()
        ->assertJsonPath('integrity.votes_reset.0.voters', 2)
        ->assertJsonPath('integrity.votes_reset.0.by', $event->user->name);
});

it('never resets closed or certified elections, and only for the owner', function () {
    [$event, $cat, $ada] = resetElection();
    castFor($this, $event, $cat, $ada, 'V1AAAAAA')->assertOk();
    $url = "/api/events/{$event->id}/reset-votes";
    $confirm = ['confirm_name' => 'Annual Elections'];

    // A co-organiser (editor) can't.
    $editor = User::factory()->create();
    EventMember::create(['event_id' => $event->id, 'user_id' => $editor->id, 'email' => $editor->email, 'role' => 'editor']);
    $this->actingAs($editor)->postJson($url, $confirm)->assertForbidden();

    // Certified results stand.
    ResultCertificate::create(['event_id' => $event->id, 'serial' => 'BLT-TEST-0001', 'payload' => [], 'signature' => str_repeat('a', 64), 'issued_at' => now()]);
    $this->actingAs($event->user)->postJson($url, $confirm)->assertStatus(422)->assertJsonValidationErrors('reset');
    ResultCertificate::query()->delete();

    // Closed: the result stands.
    $event->update(['ends_at' => now()->subMinute()]);
    $this->actingAs($event->user)->postJson($url, $confirm)->assertStatus(422)->assertJsonValidationErrors('reset');
    expect($ada->fresh()->votes)->toBe(1);

});

it('deletes ticked voters in bulk, keeping cast votes and freeing proxies', function () {
    [$event, $cat, $ada] = resetElection();
    [$v1, $v2, $v3] = Voter::where('event_id', $event->id)->orderBy('id')->get()->all();
    castFor($this, $event, $cat, $ada, 'V1AAAAAA')->assertOk();
    $v3->update(['proxy_voter_id' => $v2->id]); // V2 votes for V3

    $other = resetElection()[0];
    $stranger = Voter::where('event_id', $other->id)->first();

    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/bulk-delete", ['ids' => [$v1->id, $v2->id, $stranger->id]])
        ->assertOk()->assertJsonPath('deleted', 2)->assertJsonPath('voted', 1);

    expect(Voter::where('event_id', $event->id)->pluck('id')->all())->toBe([$v3->id])
        ->and($v3->fresh()->proxy_voter_id)->toBeNull()   // freed: votes for themself again
        ->and($ada->fresh()->votes)->toBe(1)              // V1's secret vote stays counted
        ->and(Voter::whereKey($stranger->id)->exists())->toBeTrue(); // another event's voter untouched
    expect(AuditLog::where('event_id', $event->id)->where('action', 'voters.deleted')->value('description'))
        ->toContain('2 voters removed')->toContain('1 had already voted');
});
