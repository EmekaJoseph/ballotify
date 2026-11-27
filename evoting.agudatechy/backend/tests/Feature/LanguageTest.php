<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function lgElection(array $attrs = []): array
{
    $event = Event::create(array_merge([
        'user_id' => User::factory()->create()->id, 'name' => 'Union', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'results_visibility' => 'live',
    ], $attrs));
    $cat = Category::create(['event_id' => $event->id, 'name' => 'President']);
    $ada = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    Voter::create(['event_id' => $event->id, 'code' => 'ABCD2345']);
    return [$event, $cat, $ada];
}

it('lets the organiser choose the voting pages’ default language', function () {
    [$event] = lgElection();
    $this->getJson("/api/public/events/{$event->link_token}")->assertOk()->assertJsonPath('language', 'en');

    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['language' => 'xx'])->assertStatus(422);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['language' => 'ha'])->assertOk()->assertJsonPath('language', 'ha');

    $this->getJson("/api/public/events/{$event->link_token}")->assertJsonPath('language', 'ha');
    $this->getJson("/api/public/events/{$event->link_token}/results")->assertJsonPath('event.language', 'ha');

    // Closed events still tell the page which language to use.
    $event->update(['ends_at' => now()->subMinute(), 'results_visibility' => 'private']);
    $this->getJson("/api/public/events/{$event->link_token}")->assertStatus(403)->assertJsonPath('language', 'ha');
    $this->getJson("/api/public/events/{$event->link_token}/results")->assertStatus(403)->assertJsonPath('event.language', 'ha');
});

it('answers voters in the language their page sends', function () {
    [$event, $cat, $ada] = lgElection();
    $vote = ['code' => 'ABCD2345', 'choices' => [['category_id' => $cat->id, 'candidate_id' => $ada->id]]];
    $this->postJson("/api/public/events/{$event->link_token}/vote", $vote)->assertOk();

    $this->postJson("/api/public/events/{$event->link_token}/vote", $vote, ['X-Locale' => 'fr'])
        ->assertStatus(422)->assertJsonPath('message', 'Code invalide ou déjà utilisé');
    $this->postJson("/api/public/events/{$event->link_token}/vote", $vote, ['X-Locale' => 'yo'])
        ->assertJsonPath('message', 'Kóòdù kò tọ́ tàbí a ti lò ó');
    // Placeholders are filled in the translation too.
    $this->postJson("/api/public/events/{$event->link_token}/vote", ['code' => 'ABCD2345', 'choices' => [
        ['category_id' => $cat->id, 'candidate_id' => $ada->id],
        ['category_id' => $cat->id, 'candidate_id' => Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Bola'])->id],
    ]], ['X-Locale' => 'ha'])->assertJsonPath('message', 'Zaɓi ɗan takara ɗaya kawai a “President”.');

    // Unknown or missing language: English.
    $this->postJson("/api/public/events/{$event->link_token}/vote", $vote, ['X-Locale' => 'de'])->assertJsonPath('message', 'Invalid or used code');
    $this->postJson("/api/public/events/{$event->link_token}/vote", $vote)->assertJsonPath('message', 'Invalid or used code');
});

it('has a translation for every voter-facing message in every language', function () {
    $keys = array_keys(json_decode(file_get_contents(lang_path('fr.json')), true));
    foreach (['yo', 'ha', 'ig'] as $code) {
        $other = json_decode(file_get_contents(lang_path("{$code}.json")), true);
        expect(array_keys($other))->toEqual($keys);
        foreach ($other as $key => $text) {
            preg_match_all('/:[a-z]+/', $key, $m);
            foreach ($m[0] as $placeholder) {
                expect($text)->toContain($placeholder);
            }
        }
    }
});
