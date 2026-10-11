<?php

namespace App\Services;

use App\Models\Refill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Checks a new refill and records the result on it.
// It knows nothing about water:
// it compares a check-in with its place, the user's other check-ins,
// and the rules in config/tavnit.php.
class RefillVerifier
{
    public function verify(Refill $refill): Refill
    {
        return DB::transaction(function () use ($refill) {
            // Lock this user's row until the transaction ends,
            // so two refills sent at the same moment are checked
            // one after the other and the daily count can't be beaten
            // by sending them together.
            User::whereKey($refill->user_id)->lockForUpdate()->first();

            $rules = config('tavnit.checkins');
            $spot = $refill->spot;

            $distance = (int) round($this->distanceInMetres(
                (float) $refill->latitude, (float) $refill->longitude,
                (float) $spot->latitude, (float) $spot->longitude,
            ));

            // First failing check wins; null means every check passed.
            $reason = match (true) {
                $distance > $rules['max_distance_m'] => 'too_far',
                $this->confirmedToday($refill, $rules['timezone']) >= $rules['daily_limit'] => 'daily_limit',
                $this->recentAtSameSpot($refill, $rules['repeat_window_minutes']) => 'duplicate',
                default => null,
            };

            $status = $reason === null ? 'confirmed' : 'rejected';

            // forceFill because these fields are deliberately not fillable.
            $refill->forceFill([
                'distance_m' => $distance,
                'auto_status' => $status,
                'auto_reason' => $reason,
                'status' => $status,
            ])->save();

            return $refill;
        });
    }

    // Confirmed refills by this user earlier on the same local day.
    private function confirmedToday(Refill $refill, string $timezone): int
    {
        // Midnight in Tokyo, turned back into the app's time zone (UTC)
        // before it goes into the query, because that's how logged_at is stored.
        $startOfDay = $refill->logged_at->copy()
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTimezone(config('app.timezone'));

        return Refill::where('user_id', $refill->user_id)
            ->whereKeyNot($refill->id)
            ->where('auto_status', 'confirmed')
            ->where('logged_at', '>=', $startOfDay)
            ->where('logged_at', '<=', $refill->logged_at)
            ->count();
    }

    // A confirmed refill at the same spot within the last few minutes?
    private function recentAtSameSpot(Refill $refill, int $minutes): bool
    {
        return Refill::where('user_id', $refill->user_id)
            ->where('refill_spot_id', $refill->refill_spot_id)
            ->whereKeyNot($refill->id)
            ->where('auto_status', 'confirmed')
            ->where('logged_at', '>', $refill->logged_at->copy()->subMinutes($minutes))
            ->where('logged_at', '<=', $refill->logged_at)
            ->exists();
    }

    // Distance between two points on Earth, in meters (haversine formula).
    private function distanceInMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // mean radius in metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
