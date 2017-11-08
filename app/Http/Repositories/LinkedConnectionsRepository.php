<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnectionPage;
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

    var $BASE_URL;
    const PAGE_SIZE_SECONDS = 600;

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->BASE_URL = env("LINKED_CONNECTIONS","https://graph.irail.be/sncb/connection");
    }

    /**
     * Build the URL to retrieve data from LinkedConnections
     *
     * @param Carbon $departureTime
     * @return string
     */
    private function getLinkedConnectionsURL(Carbon $departureTime): string
    {
        return $this->BASE_URL . "?departureTime=" . date_format($departureTime, 'Y-m-d\TH:i:s') . '.000Z';
    }

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime
     * @return \App\Http\Models\LinkedConnectionPage
     */
    public function getLinkedConnections(Carbon $departureTime): LinkedConnectionPage
    {
        $cacheKey = 'lcpage|' . $departureTime->getTimestamp();
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $raw = $this->getRawLinkedConnections($departureTime);
        $decoded = $raw['data'];
        $expiresAt = $raw['expiresAt'];
        $etag = $raw['etag'];
        $createdAt = $raw['createdAt'];

        $linkedConnections = [];
        if ($decoded != null && key_exists('@graph', $decoded)) {
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
        } else {
            Log::Warning("Could not retrieve data for " . $departureTime);
        }
        $linkedConnectionsPage = new LinkedConnectionPage($linkedConnections, $createdAt, $expiresAt, $etag);

        // Cache for 1 minute //TODO: lower to 30 secs
        Cache::put($cacheKey, $linkedConnectionsPage, 1);

        return $linkedConnectionsPage;
    }

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime
     * @return \App\Http\Models\LinkedConnectionPage
     */
    public function getFilteredLinkedConnections(
        Carbon $departureTime,
        $filterKey,
        $filterOperator,
        $filterValue
    ): array {

        $raw = $this->getRawLinkedConnections($departureTime);

        $filterValue = urldecode($filterValue);
        if ($filterKey == null) {
            return $raw;
        }

        foreach ($raw['data']['@graph'] as $key => &$entry) {

            if (! key_exists('arrivalDelay', $entry)) {
                $entry['arrivalDelay'] = 0;
            }
            if (! key_exists('departureDelay', $entry)) {
                $entry['departureDelay'] = 0;
            }
            $keep = false;

            switch ($filterOperator) {
                case '=':
                    $keep = ($entry[$filterKey] == $filterValue);
                    break;
                case '!=':
                    $keep = ($entry[$filterKey] != $filterValue);
                    break;
                case '<':
                    $keep = ( $entry[$filterKey] <  $filterValue);
                    break;
                case '<=':
                    $keep = ( $entry[$filterKey] <=  $filterValue);
                    break;
                case '>':
                    $keep = ( $entry[$filterKey] >  $filterValue);
                    break;
                case '>=':
                    $keep = ( $entry[$filterKey] >=  $filterValue);
                    break;
            }
            if (! $keep) {
                // Remove this from the results
                unset($raw['data']['@graph'][$key]);
            }

        }
        $raw['data']['@graph'] = array_values($raw['data']['@graph']);
        return $raw;
    }

    public function getRawLinkedConnections(Carbon $departureTime)
    {
        $departureTime = $this->getRoundedDepartureTime($departureTime);

        $pageCacheKey = 'lcraw|' . $departureTime->getTimestamp();

        // Page (array including json, etag and expiresAt), kept for 2 hours so we can reuse the etag
        if (Cache::has($pageCacheKey)) {
            /**
             * @var $previousResponse LinkedConnectionPage
             */
            $previousResponse = Cache::get($pageCacheKey);

            // Check if cache is still valid
            $now = Carbon::now();
            if ($now->lessThan($previousResponse['expiresAt']) || ($departureTime->lessThan($now) && $departureTime->diffInSeconds($now) > self::PAGE_SIZE_SECONDS)) {
                $raw = $previousResponse;
            }
        }

        // If not valid, retrieve (but try Etag as well)
        if (! isset($raw)) {

            // No previous data, or previous data too old: re-validate
            $endpoint = $this->getLinkedConnectionsURL($departureTime);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // If we have a cached old value, included header for conditional get
            if (isset($previousResponse)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'If-None-Match: "' . $previousResponse['etag'] . '"',
                ));
            }

            $headers = [];
            // this function is called by curl for each header received
            curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) // ignore invalid headers
                    {
                        return $len;
                    }

                    $name = strtolower(trim($header[0]));
                    if (! array_key_exists($name, $headers)) {
                        $headers[$name] = [trim($header[1])];
                    } else {
                        $headers[$name][] = trim($header[1]);
                    }

                    return $len;
                }
            );

            $data = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $etag = $headers['etag'][0];
            if (starts_with($etag, 'W/')) {
                $etag = substr($etag, 2);
            }
            $etag = trim($etag, '"');
            $expiresAt = Carbon::createFromTimestamp(strtotime($headers['expires'][0]));

            if (isset($raw) && ($info['http_code'] == 304 || $etag == $raw['etag'])) {
                // ETag unchanged, or header status code indicating no change
                // Just keep the raw data
            } else {
                $json = json_decode($data, true);

                $raw = ['data' => $json, 'etag' => $etag, 'expiresAt' => $expiresAt, 'createdAt' => new Carbon()];
                // Cache for 2 hours
                Cache::put($pageCacheKey, $raw, 120);
            }

        }

        return $raw;
    }

    public function getLinkedConnectionsInWindow(
        Carbon $departureTime,
        int $window = self::PAGE_SIZE_SECONDS
    ): LinkedConnectionPage {
        $departureTime = $this->getRoundedDepartureTime($departureTime);

        $cacheKey = 'lc|' . $departureTime->getTimestamp() . "|" . $window;
        if (Cache::has($cacheKey)) {
            $previousResponse = Cache::get($cacheKey);
            $previousDate = $previousResponse->getCreatedAt();
            $now = new Carbon();

            // If data isn't too old, just return for faster responses
            if (Carbon::now()
                    ->lessThan($previousResponse->getExpiresAt()) || ($departureTime->lessThan($now) && $departureTime->diffInSeconds($now) > self::PAGE_SIZE_SECONDS)) {
                return $previousResponse;
            }
        }

        $departures = [];
        $etag = "";
        $expiresAt = null;

        for ($addedSeconds = 0; $addedSeconds < $window; $addedSeconds += self::PAGE_SIZE_SECONDS) {
            $windowPage = $this->getLinkedConnections($departureTime);
            $departures = array_merge($departures, $windowPage->getLinkedConnections());

            $etag .= $windowPage->getEtag();

            if ($expiresAt == null || $windowPage->getExpiresAt()->lessThan($expiresAt)) {
                $expiresAt = $windowPage->getExpiresAt();
            }

            $departureTime = $departureTime->addSeconds(self::PAGE_SIZE_SECONDS);
        }

        // Calculate a new etag based on the concatenation of all other etags
        $etag = md5($etag);
        if (isset($previousResponse) && $etag == $previousResponse->getEtag()) {
            Log::info("Unchanged combined page!");

            // return the response with the old creation date, we can use this later on for HTTP headers
            return $previousResponse;
        }

        $combinedPage = new LinkedConnectionPage($departures, new Carbon(), $expiresAt, $etag);

        // Cache for 2 hours
        Cache::put($cacheKey, $combinedPage, 120);

        return $combinedPage;
    }

    public function getConnectionsByLimit(
        Carbon $departureTime,
        int $results
    ): LinkedConnectionPage {
        $departureTime = $this->getRoundedDepartureTime($departureTime);

        $cacheKey = 'lc|' . $departureTime->getTimestamp() . "|" . $results;
        if (Cache::has($cacheKey)) {
            $previousResponse = Cache::get($cacheKey);
            $previousDate = $previousResponse->getCreatedAt();
            $now = new Carbon();

            // If data isn't too old, just return for faster responses
            if (Carbon::now()
                    ->lessThan($previousResponse->getExpiresAt()) || ($departureTime->lessThan($now) && $departureTime->diffInSeconds($now) > self::PAGE_SIZE_SECONDS)) {
                return $previousResponse;
            }
        }

        $departures = [];
        $etag = "";
        $expiresAt = null;

        for ($addedSeconds = 0; $results < count($departures); $addedSeconds += self::PAGE_SIZE_SECONDS) {
            $windowPage = $this->getLinkedConnections($departureTime);
            $departures = array_merge($departures, $windowPage->getLinkedConnections());

            $etag .= $windowPage->getEtag();

            if ($expiresAt == null || $windowPage->getExpiresAt()->lessThan($expiresAt)) {
                $expiresAt = $windowPage->getExpiresAt();
            }

            $departureTime->addSeconds(self::PAGE_SIZE_SECONDS);
        }

        // Calculate a new etag based on the concatenation of all other etags
        $etag = md5($etag);
        if (isset($previousResponse) && $etag == $previousResponse->getEtag()) {
            // return the response with the old creation date, we can use this later on for HTTP headers
            return $previousResponse;
        }

        $combinedPage = new LinkedConnectionPage($departures, new Carbon(), $expiresAt, $etag);

        // Cache for 2 hours
        Cache::put($cacheKey, $combinedPage, 120);

        return $combinedPage;
    }

    private function getRoundedDepartureTime(Carbon $departureTime) : Carbon {
        return $departureTime->subMinute($departureTime->minute % 10)->second(0);
    }
}
