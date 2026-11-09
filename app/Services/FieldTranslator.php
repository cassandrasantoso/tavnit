<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Makes sure the free-text fields of some records have a saved English
 * translation. Anything missing or out of date goes to Google in one
 * request; if Google is slow, failing or over the monthly limit, the
 * page just shows the Japanese.
 */
class FieldTranslator
{
    public function __construct(private CloudTranslator $translator) {}

    /**
     * Returns true when every field now has an up-to-date translation,
     * false when some are still missing (the caller shows the Japanese).
     *
     * @param  Model|iterable<Model>  $records  models that use HasTranslations
     */
    public function ensure(Model|iterable $records, string $locale = 'en'): bool
    {
        $records = Collection::wrap($records instanceof Model ? [$records] : $records);
        $records->each->loadMissing('translations');

        // Every field that has Japanese but no current English.
        $missing = [];
        foreach ($records as $record) {
            foreach ($record->translatableFields() as $field) {
                $original = $record->getAttribute($field);

                if (filled($original) && $record->savedTranslation($field, $locale) === null) {
                    $missing[] = ['record' => $record, 'field' => $field, 'original' => $original];
                }
            }
        }

        if ($missing === []) {
            return true;
        }

        // No Google project set (for example on a fresh local copy): show the Japanese.
        if (blank(config('services.google_translate.project'))) {
            return false;
        }

        $texts = array_column($missing, 'original');
        $characters = array_sum(array_map('mb_strlen', $texts));

        if ($this->charactersUsedThisMonth() + $characters > config('tavnit.translation.monthly_char_limit')) {
            Log::warning('Monthly translation limit reached; showing the original text.', ['characters' => $characters]);

            return false;
        }

        try {
            $results = $this->translator->translateMany(
                $texts, 'ja', $locale, config('tavnit.translation.timeout_seconds'),
            );
        } catch (Throwable $e) {
            Log::warning('Translation failed; showing the original text.', ['error' => $e->getMessage()]);

            return false;
        }

        $this->countCharacters($characters);

        foreach ($missing as $i => $item) {
            $usedGlossary = $results[$i]['glossary'] !== null;

            $item['record']->translations()->updateOrCreate(
                ['field' => $item['field'], 'locale' => $locale],
                [
                    'text' => $usedGlossary ? $results[$i]['glossary'] : $results[$i]['plain'],
                    'source_hash' => $item['record']::sourceHash($item['original']),
                    'provider' => $usedGlossary ? 'google-v3+glossary' : 'google-v3',
                    'translated_at' => now(),
                ],
            );
        }

        $records->each->load('translations');

        return true;
    }

    public function charactersUsedThisMonth(): int
    {
        return (int) Cache::get($this->counterKey(), 0);
    }

    private function countCharacters(int $characters): void
    {
        Cache::add($this->counterKey(), 0, now()->addDays(40));
        Cache::increment($this->counterKey(), $characters);
    }

    private function counterKey(): string
    {
        return 'translation_characters:'.now()->format('Y-m');
    }
}
