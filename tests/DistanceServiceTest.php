<?php

namespace App\Tests\Service;

use App\Service\DistanceService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DistanceServiceTest extends TestCase
{
    private function makeService(): DistanceService
    {

        $http = $this->createMock(HttpClientInterface::class);
        return new DistanceService($http);
    }

    public function testHaversineParisLyon()
    {
        $svc = $this->makeService();
        // Paris (48.8566, 2.3522) → Lyon (45.7640, 4.8357)
        $m = $svc->haversine(48.8566, 2.3522, 45.7640, 4.8357);
        // ~392 km → 392000 m ± 20 km de tolérance
        $this->assertGreaterThan(370000, $m);
        $this->assertLessThan(420000, $m);
    }

    public function testEstimateMinutesFromKm()
    {
        $svc = $this->makeService();
        $minutes = $svc->estimateMinutesFromKm(9.0); // 9 km @ 4.5 km/h = 120 min
        $this->assertSame(120, $minutes);
    }

    public function testEstimateMinutesFromKmFail()
    {
        $svc = $this->makeService();
        $this->expectException(\InvalidArgumentException::class);
        $svc->estimateMinutesFromKm(9.0, 0);  // vitesse = 0 -> doit lever une exception

    }
}
