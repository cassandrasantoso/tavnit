<?php

namespace App\Models;

use App\Enums\ApplicationState;
use Database\Factories\PartnerApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Only what the shop fills in. State, claim, version and spot are changed by staff code.
#[Fillable([
    'store_name', 'store_type', 'address', 'description_ja', 'hours_note_ja',
    'message_ja', 'opening_date', 'contact_email', 'latitude', 'longitude',
])]
class PartnerApplication extends Model
{
    /** @use HasFactory<PartnerApplicationFactory> */
    use HasFactory;

    // New applications start as submitted, version 1.
    protected $attributes = [
        'state' => 'submitted',
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'state' => ApplicationState::class,
            'opening_date' => 'date',
            'publish_on' => 'date',
            'claimed_at' => 'datetime',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    // The staff member reviewing it right now, if any.
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    // The refill spot made from it, once published.
    public function spot(): BelongsTo
    {
        return $this->belongsTo(RefillSpot::class, 'refill_spot_id');
    }
}
