<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefillRequest;
use App\Http\Resources\RefillResource;
use App\Models\Refill;
use App\Services\RefillVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RefillController extends Controller
{
    // POST /api/v1/refills
    // Saves a refill and checks it (confirmed or rejected). Sending the same
    // client_id again returns the first one (200) instead of saving a second (201).
    public function store(StoreRefillRequest $request, RefillVerifier $verifier): JsonResponse
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

        // Only a brand-new refill is checked; a retry keeps its first result.
        if ($refill->wasRecentlyCreated) {
            $verifier->verify($refill);
        }

        // Read back what the database stored, so a new refill and a retry
        // look exactly the same (number types, lower-case UUID).
        $created = $refill->wasRecentlyCreated;
        $refill->refresh()->load('spot');

        return (new RefillResource($refill))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }

    // GET /api/v1/refills
    // The user's own refills, newest first, 20 per page.
    public function index(Request $request): AnonymousResourceCollection
    {
        $refills = $request->user()->refills()
            ->with('spot') // load all the spots in one query, not one per refill
            ->latest('logged_at')
            ->paginate(20);

        return RefillResource::collection($refills);
    }

    // GET /api/v1/refills/{refill}
    // One refill, only if it belongs to the user (RefillPolicy).
    public function show(Refill $refill): RefillResource
    {
        Gate::authorize('view', $refill);

        return new RefillResource($refill->load('spot'));
    }

    // GET /api/v1/me/stats
    // Confirmed refills, with the label from config/tavnit.php.
    public function stats(Request $request): JsonResponse
    {
        $confirmed = $request->user()->refills()
            ->where('status', 'confirmed')
            ->count();

        return response()->json([
            'data' => [
                'confirmed_refills' => $confirmed,
                'impact' => [
                    'value' => $confirmed, // one bottle per confirmed refill
                    'label' => config('tavnit.stats.unit_label'),
                ],
            ],
        ]);
    }
}
