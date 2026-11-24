<?php

namespace App\Console\Commands;

use App\Support\Plans;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What's left before launch: the ticked / unticked items of LAUNCH_CHECKLIST.md
 * (repo root), plus live checks of the setup this command runs against.
 * Never prints secrets, only whether they look right.
 */
class LaunchChecklist extends Command
{
    protected $signature = 'ballotify:launch';

    protected $description = 'Show what is left before launch (LAUNCH_CHECKLIST.md plus live checks)';

    /** Prices the launch plan expects in Admin -> Pricing. */
    private const TARGET_PRICES = [
        'pro_small_price_naira' => 20000,
        'pro_price_naira' => 45000,
        'pro_large_price_naira' => 105000,
        'sms_price_naira' => 10,
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('<options=bold>Live checks</> <fg=gray>(' . config('app.env') . ', ' . config('app.url') . ')</>');
        $problems = 0;
        foreach ($this->liveChecks() as [$ok, $label, $detail]) {
            $icon = $ok === true ? '<fg=green>✔</>' : ($ok === false ? '<fg=red>✘</>' : '<fg=yellow>•</>');
            if ($ok === false) $problems++;
            $this->line("  {$icon} {$label}" . ($detail ? " <fg=gray>{$detail}</>" : ''));
        }

        $file = base_path('../LAUNCH_CHECKLIST.md');
        if (! is_file($file)) {
            $this->newLine();
            $this->warn('LAUNCH_CHECKLIST.md not found in the repo root.');
            return self::SUCCESS;
        }

        $done = $todo = 0;
        $next = null;
        foreach (preg_split('/\R/', (string) file_get_contents($file)) as $line) {
            if (preg_match('/^##\s+(.*)$/', $line, $m)) {
                $this->newLine();
                $this->line('<options=bold>' . $m[1] . '</>');
            } elseif (preg_match('/^- \[( |x|X)\]\s+(.*)$/', $line, $m)) {
                $ticked = $m[1] !== ' ';
                $text = str_replace('`', '', $m[2]);
                $ticked ? $done++ : $todo++;
                $next ??= $ticked ? null : $text;
                $this->line($ticked ? "  <fg=green>[x]</> <fg=gray>{$text}</>" : "  <fg=yellow>[ ]</> {$text}");
            }
        }

        $this->newLine();
        $total = $done + $todo;
        $this->line("<options=bold>{$done} of {$total} done</>" . ($problems ? ", <fg=red>{$problems} live check(s) failing</>" : ''));
        if ($next) $this->line("<fg=cyan>Next:</> {$next}");
        else $this->info('Checklist complete. Go sell it.');
        $this->newLine();

        return self::SUCCESS;
    }

    /** @return array<int, array{0: bool|null, 1: string, 2: string}> true = good, false = fix it, null = can't tell */
    private function liveChecks(): array
    {
        $checks = [];

        // Migrations
        try {
            $migrator = app('migrator');
            $ran = $migrator->getRepository()->getRan();
            $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));
            $pending = count(array_diff($files, $ran));
            $checks[] = [$pending === 0, 'Database migrated', $pending ? "{$pending} pending: run php artisan migrate" : ''];
        } catch (\Throwable) {
            $checks[] = [false, 'Database reachable', 'could not connect'];
        }

        // Prices saved in Admin (they override the config defaults)
        $s = Plans::settings();
        $off = array_filter(self::TARGET_PRICES, fn ($v, $k) => ($s[$k] ?? null) !== $v, ARRAY_FILTER_USE_BOTH);
        $checks[] = [! $off, 'Prices match the launch plan',
            $off ? 'now ₦' . number_format($s['pro_small_price_naira']) . ' / ₦' . number_format($s['pro_price_naira'])
                . ' / ₦' . number_format($s['pro_large_price_naira']) . ', SMS ₦' . $s['sms_price_naira'] . ': set in Admin → Pricing' : ''];

        // App
        $checks[] = [! config('app.debug'), 'APP_DEBUG is off', config('app.debug') ? 'set APP_DEBUG=false in production' : ''];
        foreach (['API' => config('app.url'), 'Site' => config('app.frontend_url')] as $name => $url) {
            $https = str_starts_with((string) $url, 'https://');
            $checks[] = [$https, "{$name} uses HTTPS", $https ? '' : (string) $url];
        }

        // Payments: only the key's mode, never the key
        $key = (string) config('services.paystack.secret_key');
        $mode = str_starts_with($key, 'sk_live_') ? 'live' : (str_starts_with($key, 'sk_test_') ? 'test' : ($key === '' ? 'missing' : 'unrecognised'));
        $checks[] = [$mode === 'live', 'Paystack live key', $mode === 'live' ? '' : "key is {$mode}"];
        $paid = $this->safeCount(fn () => DB::table('payments')->where('status', 'success')->count());
        $checks[] = [$paid > 0 ? true : null, 'Successful payments recorded', $paid === null ? 'unknown' : (string) $paid];

        // Messaging
        $mailer = (string) config('mail.default');
        $checks[] = [! in_array($mailer, ['log', 'array'], true), 'Real mail sender', "mailer: {$mailer}"];
        $sms = (string) config('services.sms.driver');
        $checks[] = [$sms !== 'log', 'Real SMS provider', "driver: {$sms}"];
        $cc = config('services.sms.default_country_code');
        $checks[] = [(bool) $cc, 'SMS country code set', $cc ? "+{$cc}" : 'set SMS_DEFAULT_COUNTRY_CODE=234'];

        // Queue
        $queue = (string) config('queue.default');
        if (in_array($queue, ['sync', 'null'], true)) {
            $checks[] = [null, 'Queue', "{$queue}: messages are sent during the request (no worker needed, but slower)"];
        } else {
            $stuck = $queue === 'database'
                ? $this->safeCount(fn () => DB::table(config('queue.connections.database.table', 'jobs'))->where('created_at', '<', now()->subMinutes(5)->timestamp)->count())
                : null;
            $checks[] = [$stuck === null ? null : $stuck === 0, 'Queue worker running',
                $stuck ? "{$stuck} job(s) waiting over 5 min: start php artisan queue:work" : "driver: {$queue}"];
        }
        $failed = $this->safeCount(fn () => DB::table('failed_jobs')->count());
        $checks[] = [$failed === 0 ? true : ($failed === null ? null : false), 'No failed jobs', $failed ? "{$failed} failed: see Admin → Health" : ''];

        // Scheduler
        $beat = Cache::get('ballotify.scheduler_heartbeat');
        $recent = $beat && now()->diffInMinutes(\Illuminate\Support\Carbon::parse($beat), true) <= 5;
        $checks[] = [$recent, 'Scheduler running', $beat ? 'last run ' . \Illuminate\Support\Carbon::parse($beat)->diffForHumans() : 'never ran: add the schedule:run cron'];

        return $checks;
    }

    private function safeCount(callable $fn): ?int
    {
        try {
            return (int) $fn();
        } catch (\Throwable) {
            return null;
        }
    }
}
