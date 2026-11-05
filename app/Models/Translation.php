<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// A saved machine translation of one field of one record.
#[Fillable(['field', 'locale', 'text', 'source_hash', 'provider', 'translated_at'])]
class Translation extends Model
{
    protected function casts(): array
    {
        return [
            'translated_at' => 'datetime',
        ];
    }

    // The spot or application this text belongs to.
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
