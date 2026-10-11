<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefillSpotResource;
use App\Models\RefillSpot;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SpotController extends Controller
{
    // GET /api/v1/spots
    // Active spots only, 20 per page.
    // Removed (soft-deleted) spots are left out automatically by SoftDeletes.
    public function index(): AnonymousResourceCollection
    {
        $spots = RefillSpot::where('is_active', true)
            ->orderBy('name')
            ->paginate(20);

        return RefillSpotResource::collection($spots);
    }
}
