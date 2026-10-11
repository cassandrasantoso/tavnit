<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'refill_spot_id' => $this->refill_spot_id,
            'status' => $this->status,
            'reason' => $this->auto_reason,
            'logged_at' => $this->logged_at?->toIso8601String(),
        ];
    }
}
