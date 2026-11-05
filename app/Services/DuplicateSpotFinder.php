<?php

namespace App\Services;

use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Support\Geo;
use Illuminate\Support\Collection;

// Finds existing spots that look like the same place as an application:
// the same name once tidied up, within a short distance. Plain rules, no AI.
class DuplicateSpotFinder
{
    /**
     * @return Collection<int, array{spot: RefillSpot, distance_m: int}>
     */
    public function findFor(PartnerApplication $application): Collection
    {
        $radius = config('tavnit.review.duplicate_radius_m');
        $lat = (float) $application->latitude;
        $lng = (float) $application->longitude;
        $name = self::normaliseName($application->store_name);

        // A rough box first, so the database only sends back nearby spots.
        // One degree of latitude is about 111 km; longitude shrinks towards the poles.
        $latDelta = $radius / 111320;
        $lngDelta = $radius / (111320 * cos(deg2rad($lat)));

        return RefillSpot::query()
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->get()
            ->map(fn (RefillSpot $spot) => [
                'spot' => $spot,
                'distance_m' => (int) round(Geo::distanceInMetres(
                    $lat, $lng, (float) $spot->latitude, (float) $spot->longitude,
                )),
            ])
            ->filter(fn (array $match) => $match['distance_m'] <= $radius
                && self::normaliseName($match['spot']->name) === $name)
            ->sortBy('distance_m')
            ->values();
    }

    // Makes names comparable: full-width letters, digits and spaces become half-width,
    // half-width katakana becomes full-width (ｶﾌｪ → カフェ), then spaces go and letters
    // are lower-cased.
    public static function normaliseName(string $name): string
    {
        $name = mb_convert_kana($name, 'asKV');
        $name = preg_replace('/\s+/u', '', $name);

        return mb_strtolower($name);
    }
}
