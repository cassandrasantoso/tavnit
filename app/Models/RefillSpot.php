<?php

namespace App\Models;

use Database\Factories\RefillSpotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description_ja', 'latitude', 'longitude', 'is_active'])]
class RefillSpot extends Model
{
    /** @use HasFactory<RefillSpotFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }
}
