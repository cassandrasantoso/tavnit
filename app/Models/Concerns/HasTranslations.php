<?php

namespace App\Models\Concerns;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * For models with free-text fields that staff can read in English.
 *
 * The model lists those fields in translatableFields(). Names and
 * addresses are never listed: they are shown as submitted.
 */
trait HasTranslations
{
    /** @return list<string> */
    abstract public function translatableFields(): array;

    public static function bootHasTranslations(): void
    {
        // Soft-deleted spots can be restored, so keep their translations
        // until the record is really gone.
        $event = in_array(SoftDeletes::class, class_uses_recursive(static::class), true)
            ? 'forceDeleted'
            : 'deleted';

        static::registerModelEvent($event, function ($model) {
            $model->translations()->delete();
        });
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * The saved translation of a field, or null if there is none or the
     * original has changed since it was translated.
     */
    public function savedTranslation(string $field, string $locale = 'en'): ?string
    {
        $original = $this->getAttribute($field);

        if (blank($original)) {
            return null;
        }

        $translation = $this->translations
            ->first(fn (Translation $t) => $t->field === $field && $t->locale === $locale);

        if (! $translation || $translation->source_hash !== static::sourceHash($original)) {
            return null;
        }

        return $translation->text;
    }

    public static function sourceHash(string $text): string
    {
        return hash('sha256', $text);
    }
}
