<?php

namespace App\Support;

class ApproximateLocation
{
    /**
     * Displace an exact coordinate by a stable, deterministic offset (up to
     * $radiusMeters) derived from $seed. The same inputs always produce the
     * same public location, so the map marker never jumps around between
     * page loads.
     *
     * @return array{latitude: float, longitude: float}
     */
    public static function displace(float $latitude, float $longitude, string $seed, int $radiusMeters = 1500): array
    {
        $hash = crc32($seed);

        $angle = (($hash % 3600) / 3600) * 2 * M_PI;
        $distance = ((intdiv($hash, 3600) % 1000) / 1000) * $radiusMeters;

        $earthRadius = 6371000;

        $latOffset = rad2deg(($distance * cos($angle)) / $earthRadius);
        $lngOffset = rad2deg(($distance * sin($angle)) / ($earthRadius * cos(deg2rad($latitude))));

        return [
            'latitude' => round($latitude + $latOffset, 7),
            'longitude' => round($longitude + $lngOffset, 7),
        ];
    }
}
