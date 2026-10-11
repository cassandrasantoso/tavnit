<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// The status fields are NOT fillable: only the checks (and later staff) set them.
#[Fillable(['user_id', 'refill_spot_id', 'client_id', 'latitude', 'longitude', 'logged_at'])]
class Refill extends Model
{
    use HasFactory;

    // Defaults for a new, unsaved refill, so the reply shows "pending" straight away.
    protected $attributes = [
        'auto_status' => 'pending',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function spot(): BelongsTo
    {
        return $this->belongsTo(RefillSpot::class, 'refill_spot_id');
    }
}
