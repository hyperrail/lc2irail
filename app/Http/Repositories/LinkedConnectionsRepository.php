<?php

namespace App\Http\Repositories;

use Cache\Adapter\Apc\ApcCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Models\LinkedConnection;

/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class LinkedConnectionsRepository implements LinkedConnectionsRepositoryContract
{

    const BASE_URL = "http://graph.spitsgids.be/";
    private static $cache;

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct()
    {

    }


    /**
     * Build the URL to retrieve data from LinkedConnections
     *
     * @param Carbon $departureTime
     * @return string
     */
    private static function getLinkedConnectionsURL(Carbon $departureTime): string
    {
        return self::BASE_URL ;//. "?departureTime=" . date_format($departureTime, 'Y-m-d\TH:i');
    }

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime
     * @return array
     */
    public function getLinkedConnections(Carbon $departureTime): array
    {
        if (Cache::has('lc:' . $departureTime->getTimestamp())) {
            return Cache::get('lc:' . $departureTime->getTimestamp());
        }

        $endpoint = self::getLinkedConnectionsURL($departureTime);

        Log::info("Retrieving data from {$endpoint}");

        $ld = file_get_contents($endpoint);

        $decoded = json_decode($ld, true);

        $linkedConnections = [];
        foreach ($decoded['@graph'] as $entry) {
            $linkedConnections[] = new LinkedConnection($entry['@id'],
                $entry['departureStop'],
                strtotime($entry['departureTime']),
                $entry['departureDelay'],
                $entry['arrivalStop'],
                strtotime($entry['arrivalTime']),
                $entry['arrivalDelay'],
                $entry['gtfs:trip'],
                $entry['gtfs:route']
            );
        }

        Cache::put('lc:' . $departureTime->getTimestamp(), $linkedConnections, 1);

        return $linkedConnections;
    }


    public function getLinkedConnectionsInWindow(Carbon $departureTime, int $window = 600): array
    {
        if (Cache::has('lc:' . $departureTime->getTimestamp() . ":" . $window)) {
            return Cache::get('lc:' . $departureTime->getTimestamp() . ":" . $window);
        }

        $departures = [];
        for ($increment = 0; $increment < $window; $increment += 600) {
            //array_merge($departures,  $this->getLinkedConnections($request->getDateTime()->addSeconds($increment)));
            $departures = array_merge($departures,
                $this->getLinkedConnections((new Carbon("2015-10-01T10:00"))->addSeconds($increment)));
        }

        Cache::put('lc:' . $departureTime->getTimestamp() . ":" . $window, $departures, 1);

        return $departures;
    }
}
