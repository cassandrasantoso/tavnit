<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefillRequest;
use App\Http\Resources\RefillResource;
use App\Models\Refill;
use Illuminate\Http\JsonResponse;

class RefillController extends Controller
{
    // POST /api/v1/refills
    // Saves a refill as pending. Sending the same client_id again returns the
    // first one (200) instead of saving a second (201).
    public function store(StoreRefillRequest $request): JsonResponse
    {
        $data = $request->validated();

        // createOrFirst tries to insert and, if the unique (user_id, client_id)
        // index says it already exists, fetches that row instead. That also
        // covers two copies arriving at the same moment.
        $refill = Refill::createOrFirst(
            ['user_id' => $request->user()->id, 'client_id' => $data['client_id']],
            [
                'refill_spot_id' => $data['refill_spot_id'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'logged_at' => now(),
            ],
        );

        // Read back what the database stored, so a new refill and a retry
        // look exactly the same (number types, lower-case UUID).
        $created = $refill->wasRecentlyCreated;
        $refill->refresh();

        return (new RefillResource($refill))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}
