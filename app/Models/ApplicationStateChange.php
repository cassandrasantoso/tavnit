<?php

namespace App\Models;

use App\Enums\ApplicationState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One step in an application's history. Only ever added, never edited.
#[Fillable(['user_id', 'from_state', 'to_state', 'reason', 'note'])]
class ApplicationStateChange extends Model
{
    // Only created_at is kept; Laravel skips updated_at when this is null.
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'from_state' => ApplicationState::class,
            'to_state' => ApplicationState::class,
            'created_at' => 'datetime',
        ];
    }

    // The staff member who made the change.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
