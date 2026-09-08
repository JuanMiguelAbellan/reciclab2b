<?php

namespace Tests\Unit;

use App\Support\ApproximateLocation;
use PHPUnit\Framework\TestCase;

class ApproximateLocationTest extends TestCase
{
    public function test_the_same_inputs_always_produce_the_same_output(): void
    {
        $first = ApproximateLocation::displace(41.6488, -0.8891, 'company:1:exact');
        $second = ApproximateLocation::displace(41.6488, -0.8891, 'company:1:exact');

        $this->assertSame($first, $second);
    }

    public function test_a_different_seed_produces_a_different_location(): void
    {
        $a = ApproximateLocation::displace(41.6488, -0.8891, 'company:1:exact');
        $b = ApproximateLocation::displace(41.6488, -0.8891, 'company:2:exact');

        $this->assertNotSame($a, $b);
    }

    public function test_the_displacement_stays_within_the_given_radius(): void
    {
        $latitude = 41.6488;
        $longitude = -0.8891;
        $radiusMeters = 1500;

        $result = ApproximateLocation::displace($latitude, $longitude, 'seed', $radiusMeters);

        $earthRadius = 6371000;
        $latDelta = deg2rad($result['latitude'] - $latitude);
        $lngDelta = deg2rad($result['longitude'] - $longitude);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($latitude)) * cos(deg2rad($result['latitude'])) * sin($lngDelta / 2) ** 2;
        $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        $this->assertLessThanOrEqual($radiusMeters, $distance);
    }
}
