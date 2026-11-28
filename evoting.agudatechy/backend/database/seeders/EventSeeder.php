<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Nomination;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sample events for trying the dashboard, owned by the seeded admin:
 *
 *   1. A students' union election that is open now (live results, part-voted).
 *   2. A church council election that closed last week (final results).
 *   3. A cooperative AGM that opens next week and is collecting nominations.
 *
 * Votes are added the same way a real ballot adds them: running totals only,
 * a receipt per ballot, and the time each code was used. Safe to run again:
 * events that already exist (by name, for this owner) are skipped.
 *
 *   php artisan db:seed --class=EventSeeder
 */
class EventSeeder extends Seeder
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const FIRST = ['Ada', 'Bola', 'Chidi', 'Damilola', 'Emeka', 'Funke', 'Gbenga', 'Halima', 'Ifeoma', 'Jide', 'Kemi', 'Lanre',
        'Musa', 'Ngozi', 'Obinna', 'Peace', 'Rukayat', 'Seun', 'Tolu', 'Uche', 'Victor', 'Wale', 'Yetunde', 'Zainab', 'Aisha', 'Bayo',
        'Chioma', 'Dayo', 'Efe', 'Femi', 'Grace', 'Hassan', 'Ibrahim', 'Joy', 'Kunle', 'Lola', 'Nkechi', 'Ola', 'Sade', 'Tunde'];
    private const LAST = ['Okafor', 'Adeyemi', 'Musa', 'Eze', 'Bello', 'Okonkwo', 'Ogunleye', 'Abubakar', 'Nwosu', 'Balogun', 'Ibrahim',
        'Adebayo', 'Okoro', 'Lawal', 'Uzor', 'Olawale', 'Danjuma', 'Afolabi', 'Chukwu', 'Sanni'];

    public function run(): void
    {
        $owner = User::where('email', 'admin@agudatechy.com')->first();
        if (! $owner) {
            $this->command?->warn('Run UserSeeder first: the sample events belong to admin@agudatechy.com.');
            return;
        }

        $this->seedEvent($owner, [
            'name' => 'Students’ Union Elections 2026',
            'description' => "Elect your Students’ Union executive for the 2026/2027 session.\nUse the code sent to your school email. One code, one ballot.",
            'starts_at' => now()->subDays(2)->setTime(8, 0),
            'ends_at' => now()->addDays(3)->setTime(18, 0),
            'results_visibility' => Event::RESULTS_LIVE,
        ], [
            ['name' => 'President', 'candidates' => [
                ['Tolu Adebayo', 'Final year, Computer Science. “A union that listens.”'],
                ['Chidi Okafor', 'Former class rep, Law. Focus: hostels and transport.'],
                ['Zainab Bello', 'Debate club president, Economics.'],
            ], 'weights' => [5, 4, 3]],
            ['name' => 'Vice President', 'candidates' => [
                ['Kemi Ogunleye', 'Mass Communication, 300 level.'],
                ['Musa Danjuma', 'Engineering, 400 level.'],
            ], 'weights' => [3, 2]],
            ['name' => 'General Secretary', 'candidates' => [
                ['Ifeoma Nwosu', null], ['Seun Afolabi', null], ['Halima Abubakar', null],
            ], 'weights' => [2, 2, 3]],
            ['name' => 'Senate Representatives', 'max_choices' => 3, 'shuffle_candidates' => true, 'candidates' => [
                ['Bayo Lawal', null], ['Grace Eze', null], ['Hassan Ibrahim', null], ['Joy Okoro', null], ['Victor Uzor', null],
            ], 'weights' => [4, 5, 3, 4, 2]],
        ], voters: 120, voted: 71);

        $this->seedEvent($owner, [
            'name' => 'Grace Chapel Church Council Election',
            'description' => 'Choose five members of the Church Council, a Secretary and a Treasurer.',
            'starts_at' => now()->subDays(9)->setTime(9, 0),
            'ends_at' => now()->subDays(7)->setTime(17, 0),
            'results_visibility' => Event::RESULTS_AFTER_CLOSE,
        ], [
            ['name' => 'Church Council', 'max_choices' => 5, 'shuffle_candidates' => true, 'candidates' => [
                ['Deacon Femi Balogun', null], ['Mrs Ngozi Chukwu', null], ['Bro. Wale Olawale', null], ['Sis. Peace Sanni', null],
                ['Elder Gbenga Adeyemi', null], ['Mrs Lola Okonkwo', null], ['Bro. Efe Ibrahim', null],
            ], 'weights' => [6, 5, 4, 5, 6, 3, 2]],
            ['name' => 'Secretary', 'candidates' => [['Sis. Yetunde Afolabi', null], ['Bro. Obinna Eze', null]], 'weights' => [3, 2]],
            ['name' => 'Treasurer', 'candidates' => [['Deacon Lanre Musa', null], ['Mrs Rukayat Bello', null]], 'weights' => [1, 1]],
        ], voters: 80, voted: 62);

        $this->seedNominations($owner);
    }

    /** @param array<int, array{name: string, candidates: array, weights: int[], max_choices?: int|null, shuffle_candidates?: bool}> $ballot */
    private function seedEvent(User $owner, array $attrs, array $ballot, int $voters, int $voted): void
    {
        if (Event::where('user_id', $owner->id)->where('name', $attrs['name'])->exists()) {
            $this->command?->line("Skipped “{$attrs['name']}” (already there).");
            return;
        }

        DB::transaction(function () use ($owner, $attrs, $ballot, $voters, $voted) {
            $event = Event::create($attrs + ['user_id' => $owner->id, 'link_token' => (string) Str::uuid()]);

            // The ballot.
            $cats = [];
            foreach ($ballot as $c) {
                $category = Category::create([
                    'event_id' => $event->id,
                    'name' => $c['name'],
                    'max_choices' => array_key_exists('max_choices', $c) ? $c['max_choices'] : 1,
                    'shuffle_candidates' => $c['shuffle_candidates'] ?? false,
                ]);
                $ids = [];
                foreach ($c['candidates'] as [$name, $description]) {
                    $ids[] = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => $name, 'description' => $description])->id;
                }
                $cats[] = ['model' => $category, 'ids' => $ids, 'weights' => $c['weights'], 'limit' => $category->max_choices];
            }

            // The voter list, with codes.
            $rows = [];
            $taken = [];
            for ($i = 0; $i < $voters; $i++) {
                $first = self::FIRST[$i % count(self::FIRST)];
                $last = self::LAST[($i * 7 + intdiv($i, count(self::FIRST))) % count(self::LAST)];
                do { $code = $this->code(); } while (isset($taken[$code]));
                $taken[$code] = true;
                $rows[] = [
                    'event_id' => $event->id,
                    'name' => "{$first} {$last}",
                    'email' => strtolower("{$first}.{$last}.{$i}@example.com"),
                    'phone' => $i % 3 === 0 ? null : '+23480' . str_pad((string) (31000000 + $i * 7919 % 9000000), 8, '0', STR_PAD_LEFT),
                    'member_id' => 'MEM-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'code' => $code,
                    'invited_at' => $i < $voters - 10 ? $event->starts_at->copy()->subDay() : null,
                    'invited_via' => $i < $voters - 10 ? 'email' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Voter::insert($rows);

            // Ballots: spread over the voting window so far, totals only (secret ballot).
            $from = $event->starts_at;
            $to = min(now()->subMinutes(5), $event->ends_at);
            $span = max(60, $to->getTimestamp() - $from->getTimestamp());
            $ids = Voter::where('event_id', $event->id)->orderBy('id')->limit($voted)->pluck('id');
            foreach ($ids as $n => $voterId) {
                // Busier near the start and the end, like a real election.
                $x = ($n + 0.5) / max(1, $voted);
                $shape = $x < .5 ? 2 * $x * $x : 1 - 2 * (1 - $x) * (1 - $x);
                $at = Carbon::createFromTimestamp($from->getTimestamp() + (int) ($span * (0.15 * $x + 0.85 * $shape)));

                foreach ($cats as $cat) {
                    if (random_int(1, 100) <= 4) {
                        continue; // left this category blank
                    }
                    $picks = $this->pick($cat['ids'], $cat['weights'], $cat['limit'] === 1 ? 1 : random_int(1, $cat['limit'] ?? count($cat['ids'])));
                    Candidate::whereIn('id', $picks)->increment('votes');
                    $cat['model']->increment('ballots');
                }
                Voter::whereKey($voterId)->update(['used_at' => $at]);
                BallotReceipt::issue($event->id);
            }

            AuditLog::create(['event_id' => $event->id, 'user_id' => $owner->id, 'action' => 'event.created',
                'description' => "Event “{$event->name}” created (sample data)", 'during_voting' => false]);
            $this->command?->info("Created “{$event->name}”: {$voters} voters, {$voted} voted.");
        });
    }

    /** An upcoming AGM collecting nominations, with suggested names and some already in. */
    private function seedNominations(User $owner): void
    {
        $name = 'Lagos Traders Cooperative AGM';
        if (Event::where('user_id', $owner->id)->where('name', $name)->exists()) {
            $this->command?->line("Skipped “{$name}” (already there).");
            return;
        }

        DB::transaction(function () use ($owner, $name) {
            $event = Event::create([
                'user_id' => $owner->id,
                'name' => $name,
                'description' => 'Annual general meeting: election of executive officers. Members, put forward the people you want on the ballot.',
                'link_token' => (string) Str::uuid(),
                'starts_at' => now()->addDays(7)->setTime(10, 0),
                'ends_at' => now()->addDays(7)->setTime(16, 0),
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'nominations_enabled' => true,
                'nominations_ends_at' => now()->addDays(4)->setTime(23, 59),
            ]);

            $nominations = [
                'Chairperson' => [['Alhaji Musa Bello', 6], ['Mrs Funke Adeyemi', 4], ['Chief Obinna Okafor', 2]],
                'Secretary' => [['Mr Dayo Lawal', 3], ['Miss Chioma Nwosu', 3]],
                'Treasurer' => [['Mrs Sade Olawale', 5], ['Mr Kunle Sanni', 1]],
            ];
            $suggestions = ['Chairperson' => ['Alhaji Musa Bello', 'Mrs Funke Adeyemi'], 'Secretary' => [], 'Treasurer' => ['Mrs Sade Olawale']];

            foreach ($nominations as $post => $people) {
                $category = Category::create([
                    'event_id' => $event->id, 'name' => $post, 'max_choices' => 1,
                    'nominee_suggestions' => $suggestions[$post] ?: null,
                ]);
                $k = 0;
                foreach ($people as [$person, $times]) {
                    for ($i = 0; $i < $times; $i++, $k++) {
                        Nomination::create([
                            'event_id' => $event->id,
                            'category_id' => $category->id,
                            'nominee_name' => $person,
                            'normalized_name' => Nomination::normalize($person),
                            'reason' => $i === 0 ? 'Has served the cooperative faithfully.' : null,
                            'ip_hash' => hash('sha256', "sample-ip-{$post}-{$k}"),
                            'device_hash' => hash('sha256', "sample-device-{$post}-{$k}"),
                        ]);
                    }
                }
            }

            AuditLog::create(['event_id' => $event->id, 'user_id' => $owner->id, 'action' => 'event.created',
                'description' => "Event “{$event->name}” created (sample data)", 'during_voting' => false]);
            $this->command?->info("Created “{$name}”: nominations open, voting next week.");
        });
    }

    /** Weighted pick of $n different candidates (a popular candidate gets more votes). */
    private function pick(array $ids, array $weights, int $n): array
    {
        $out = [];
        $pool = array_combine($ids, $weights);
        for ($i = 0; $i < $n && $pool; $i++) {
            $r = random_int(1, array_sum($pool));
            foreach ($pool as $id => $w) {
                if (($r -= $w) <= 0) {
                    $out[] = $id;
                    unset($pool[$id]);
                    break;
                }
            }
        }
        return $out;
    }

    private function code(): string
    {
        $max = strlen(self::CODE_ALPHABET) - 1;
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }
        return $code;
    }
}
