<?php

namespace App\Http\Repositories;

use Cache;
use Carbon\Carbon;
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

    const BASE_URL = "http://belgium.linkedconnections.org/sncb/connections";

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
        return self::BASE_URL . "?departureTime=" . date_format($departureTime, 'Y-m-d\TH:i');
    }

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime
     * @return \App\Http\Models\DeparturesLiveboard[]
     */
    public function getLinkedConnections(Carbon $departureTime): array
    {
        $departureTime = $this->getRoundedDepartureTime($departureTime);
        $cacheKey = 'lc|' . $departureTime->getTimestamp();
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $endpoint = self::getLinkedConnectionsURL($departureTime);

        Log::info("Retrieving data from {$endpoint}, cache missed!");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $ld = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($ld, true);

        $linkedConnections = [];
        foreach ($decoded['@graph'] as $entry) {

            $arrivalDelay = key_exists('arrivalDelay', $entry) ? $entry['arrivalDelay'] : 0;
            $departureDelay = key_exists('departureDelay', $entry) ? $entry['departureDelay'] : 0;

            $linkedConnections[] = new LinkedConnection($entry['@id'],
                $entry['departureStop'],
                strtotime($entry['departureTime']),
                $departureDelay,
                $entry['arrivalStop'],
                strtotime($entry['arrivalTime']),
                $arrivalDelay,
                $entry['gtfs:trip'],
                $entry['gtfs:route']
            );
        }

        $expiresAt = Carbon::now()->addSeconds(15);
        Cache::put($cacheKey, $linkedConnections, $expiresAt);
        return $linkedConnections;
    }


    public function getLinkedConnectionsInWindow(Carbon $departureTime, int $window = 600): array
    {
        $departureTime = $this->getRoundedDepartureTime($departureTime);
        if (Cache::has('lc|' . $departureTime->getTimestamp() . ":" . $window)) {
            return Cache::get('lc|' . $departureTime->getTimestamp() . ":" . $window);
        }

        $departures = [];
        $pageWindow = 600;
        for ($addedSeconds = 0; $addedSeconds < $window; $addedSeconds += $pageWindow) {
            $windowDepartures = $this->getLinkedConnections($departureTime);
            $departureTime->addSeconds($pageWindow);
            $departures = array_merge($departures, $windowDepartures);
        }

        $expiresAt = Carbon::now()->addSeconds(15);
        Cache::put('lc:' . $departureTime->getTimestamp() . ":" . $window, $departures, $expiresAt);

        return $departures;
    }

    private function getRoundedDepartureTime(Carbon $departureTime) : Carbon {
        return $departureTime->subMinute($departureTime->minute % 10)->second(0);
    }
}
