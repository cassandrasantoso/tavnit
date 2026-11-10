<?php

namespace App\Console\Commands;

use App\Models\PartnerApplication;
use App\Models\Refill;
use App\Models\RefillSpot;
use App\Models\User;
use App\Services\CloudTranslator;
use App\Services\DuplicateSpotFinder;
use App\Services\FieldTranslator;
use App\Services\RefillVerifier;
use App\Support\Romaji;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs the made-up cases in eval/cases.json against the real code and
 * prints how often each rule gets them right.
 *
 * Named RunEval because "eval" is a reserved word in PHP; the command
 * is still called "php artisan eval". Everything it saves to the
 * database is rolled back at the end.
 */
class RunEval extends Command
{
    protected $signature = 'eval {--write : Also save the results to eval/RESULTS.md}';

    protected $description = 'Check the rules against the cases in eval/cases.json';

    /** @var list<string> Markdown lines for eval/RESULTS.md */
    private array $report = [];

    public function handle(): int
    {
        $cases = json_decode(file_get_contents(base_path('eval/cases.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->report = ['# Eval results', '', 'Run on '.now()->toDateTimeString().' with `php artisan eval --write`. All cases are made up.', ''];

        $this->readings($cases['readings']);

        DB::beginTransaction();

        try {
            $this->duplicates($cases['duplicates']);
            $this->refills($cases['refills']);
            $this->glossary($cases['glossary']);
            $this->timing();
        } finally {
            DB::rollBack();
        }

        if ($this->option('write')) {
            file_put_contents(base_path('eval/RESULTS.md'), implode("\n", $this->report)."\n");
            $this->info('Saved eval/RESULTS.md');
        }

        return self::SUCCESS;
    }

    private function readings(array $cases): void
    {
        $rows = array_map(function (array $case) {
            $got = Romaji::fromKana($case['kana']);

            return [$case['kana'], $case['expected'], $got, $this->mark($got === $case['expected']), $case['note'] ?? ''];
        }, $cases);

        $this->section('Readings in Latin letters', ['Kana', 'Expected', 'Got', 'OK', 'Note'], $rows);
    }

    private function duplicates(array $cases): void
    {
        $finder = app(DuplicateSpotFinder::class);

        $rows = array_map(function (array $case) use ($finder) {
            // Each case gets its own place far from the others, so they can't match each other.
            static $offset = 0;
            $latitude = 35.0 + ($offset++ * 0.01);

            RefillSpot::factory()->create(['name' => $case['spot'], 'latitude' => $latitude, 'longitude' => 139.7]);
            $application = PartnerApplication::factory()->create([
                'store_name' => $case['application'],
                'latitude' => $latitude + $case['metres'] / 111320,
                'longitude' => 139.7,
            ]);

            $flagged = $finder->findFor($application)->isNotEmpty();

            return [$case['case'], $case['expected'] ? 'flag' : 'no flag', $flagged ? 'flag' : 'no flag', $this->mark($flagged === $case['expected']), $case['note'] ?? ''];
        }, $cases);

        $this->section('Duplicate warning', ['Case', 'Expected', 'Got', 'OK', 'Note'], $rows);
    }

    private function refills(array $cases): void
    {
        $verifier = app(RefillVerifier::class);
        $tokyo = config('tavnit.checkins.timezone');

        $rows = array_map(function (array $case) use ($verifier, $tokyo) {
            $user = User::factory()->create();
            $spot = RefillSpot::factory()->create(['latitude' => 35.6595, 'longitude' => 139.7005]);

            // Earlier refills that already passed: listed one by one, or a count spaced 30 minutes apart.
            $earlier = $case['earlier'] ?? [];
            for ($i = 0; $i < ($case['earlier_count'] ?? 0); $i++) {
                $earlier[] = [
                    'at' => Carbon::parse($case['earlier_from'], $tokyo)->addMinutes(30 * $i)->format('Y-m-d H:i'),
                    'same_spot' => false,
                ];
            }

            foreach ($earlier as $before) {
                Refill::factory()->confirmed()->create([
                    'user_id' => $user->id,
                    'refill_spot_id' => $before['same_spot'] ? $spot->id : RefillSpot::factory()->create()->id,
                    'logged_at' => Carbon::parse($before['at'], $tokyo)->utc(),
                ]);
            }

            $refill = Refill::factory()->create([
                'user_id' => $user->id,
                'refill_spot_id' => $spot->id,
                'latitude' => 35.6595 + $case['metres'] / 111320,
                'longitude' => 139.7005,
                'logged_at' => Carbon::parse($case['at'], $tokyo)->utc(),
            ]);

            $got = $verifier->verify($refill)->auto_reason ?? 'confirmed';

            return [$case['case'], $case['expected'], $got, $this->mark($got === $case['expected']), ''];
        }, $cases);

        $this->section('Refill checks', ['Case', 'Expected', 'Got', 'OK', 'Note'], $rows);
    }

    // The before/after rows: each sentence with and without the glossary.
    private function glossary(array $cases): void
    {
        if (blank(config('services.google_translate.project'))) {
            $this->skip('Glossary: with vs without', 'no Google project in .env (GOOGLE_CLOUD_PROJECT).');

            return;
        }

        if (blank(config('services.google_translate.glossary'))) {
            $this->skip('Glossary: with vs without', 'no glossary in .env (TRANSLATE_GLOSSARY_ID).');

            return;
        }

        try {
            $results = app(CloudTranslator::class)->translateMany(array_column($cases, 'ja'));
        } catch (Throwable $e) {
            $this->skip('Glossary: with vs without', 'Google returned an error: '.$e->getMessage());

            return;
        }

        $rows = [];
        foreach ($cases as $i => $case) {
            $plain = $results[$i]['plain'];
            $glossary = $results[$i]['glossary'] ?? '';
            $rows[] = [
                $case['ja'],
                $case['term'],
                $plain.' '.$this->mark(str_contains(mb_strtolower($plain), $case['term'])),
                $glossary.' '.$this->mark(str_contains(mb_strtolower($glossary), $case['term'])),
                '',
            ];
        }

        $this->section('Glossary: with vs without', ['Japanese', 'Our term', 'Without glossary', 'With glossary', 'Note'], $rows);
    }

    // How long a reviewer waits the first time an application is read in English, and after that.
    private function timing(): void
    {
        if (blank(config('services.google_translate.project'))) {
            $this->skip('First English view vs saved', 'no Google project in .env.');

            return;
        }

        $application = PartnerApplication::factory()->create([
            'description_ja' => '店内で給水できます。マイボトルをお持ちください。',
            'hours_note_ja' => '平日 9:00–18:00、土日は休み',
            'message_ja' => '来月オープン予定です。よろしくお願いします。',
        ]);
        $translator = app(FieldTranslator::class);

        $first = $this->timeMs(fn () => $translator->ensure($application));
        $saved = $this->timeMs(fn () => $translator->ensure($application->fresh()));

        $this->section('First English view vs saved', ['Visit', 'Time (ms)', '', '', ''], [
            ['First view (asks Google)', (string) $first, '', '', ''],
            ['Next views (saved English)', (string) $saved, '', '', ''],
        ]);
    }

    private function timeMs(callable $callback): int
    {
        $start = hrtime(true);
        $callback();

        return (int) round((hrtime(true) - $start) / 1_000_000);
    }

    private function section(string $title, array $headers, array $rows): void
    {
        $score = count(array_filter($rows, fn (array $row) => str_contains($row[3] ?? '', '✓')));
        $summary = in_array('OK', $headers, true) ? " ({$score} of ".count($rows).')' : '';

        $this->newLine();
        $this->line("<options=bold>{$title}{$summary}</>");
        $this->table($headers, $rows);

        $this->report[] = "## {$title}{$summary}";
        $this->report[] = '';
        $this->report[] = '| '.implode(' | ', $headers).' |';
        $this->report[] = '|'.str_repeat(' --- |', count($headers));
        foreach ($rows as $row) {
            $this->report[] = '| '.implode(' | ', array_map(fn ($cell) => str_replace('|', '\\|', (string) $cell), $row)).' |';
        }
        $this->report[] = '';
    }

    private function skip(string $title, string $reason): void
    {
        $this->newLine();
        $this->line("<options=bold>{$title}</>: skipped, {$reason}");
        $this->report[] = "## {$title}";
        $this->report[] = '';
        $this->report[] = "Skipped: {$reason}";
        $this->report[] = '';
    }

    private function mark(bool $ok): string
    {
        return $ok ? '✓' : '✗';
    }
}
