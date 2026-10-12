<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefillRequest extends FormRequest
{
    // Only logged-in app users reach this; auth:sanctum checks that.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid'],
            // The spot must exist, be active, and not be removed.
            'refill_spot_id' => [
                'required',
                'integer',
                Rule::exists('refill_spots', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
