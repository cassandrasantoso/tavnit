<?php

namespace App\Models;

use App\Enums\ApplicationState;
use App\Models\Concerns\HasTranslations;
use Database\Factories\PartnerApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;

// Only what the shop fills in. State, claim, version and spot are changed by staff code.
#[Fillable([
    'store_name', 'store_name_kana', 'store_name_en', 'store_type', 'address', 'description_ja', 'hours_note_ja',
    'message_ja', 'opening_date', 'contact_email', 'latitude', 'longitude',
])]
class PartnerApplication extends Model
{
    /** @use HasFactory<PartnerApplicationFactory> */
    use HasFactory, HasTranslations;

    // New applications start as submitted, version 1.
    protected $attributes = [
        'state' => 'submitted',
        'version' => 1,
    ];

    // Free text staff can read in English. Store name and address are shown as submitted.
    public function translatableFields(): array
    {
        return ['description_ja', 'hours_note_ja', 'message_ja'];
    }

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

    // The type in the page's language. A type we have no wording for is shown as stored.
    public function storeTypeLabel(): string
    {
        $key = "applications.store_types.{$this->store_type}";

        return Lang::has($key) ? __($key) : $this->store_type;
    }

    // The staff member reviewing it right now, if any.
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    // Every state change, oldest first: the audit trail.
    public function stateChanges(): HasMany
    {
        return $this->hasMany(ApplicationStateChange::class)->oldest('id');
    }

    // The refill spot made from it, once published.
    public function spot(): BelongsTo
    {
        return $this->belongsTo(RefillSpot::class, 'refill_spot_id');
    }
}
