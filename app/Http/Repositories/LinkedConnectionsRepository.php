<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnection;
use App\Http\Models\LinkedConnectionPage;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class LinkedConnectionsRepository implements LinkedConnectionsRepositoryContract
{

    private $rawLinkedConnectionsSource;

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->rawLinkedConnectionsSource = app(LinkedConnectionsRawRepositoryContract::class);
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
    ): array
    {

        $raw = $this->rawLinkedConnectionsSource->getRawLinkedConnections($departureTime);

        $filterValue = urldecode($filterValue);
        if ($filterKey == null || $filterOperator == null || $filterValue == null) {
            return $raw;
        }

        foreach ($raw['data'] as $key => &$entry) {

            if (!key_exists('arrivalDelay', $entry)) {
                $entry['arrivalDelay'] = 0;
            }
            if (!key_exists('departureDelay', $entry)) {
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
                    $keep = ($entry[$filterKey] < $filterValue);
                    break;
                case '<=':
                    $keep = ($entry[$filterKey] <= $filterValue);
                    break;
                case '>':
                    $keep = ($entry[$filterKey] > $filterValue);
                    break;
                case '>=':
                    $keep = ($entry[$filterKey] >= $filterValue);
                    break;
            }
            if (!$keep) {
                // Remove this from the results
                unset($raw['data'][$key]);
            }

        }

        $raw['data'] = array_values($raw['data']);
        return $raw;
    }

    public function getLinkedConnectionsInWindow(
        $pointer,
        int $window = 3600
    ): LinkedConnectionPage
    {
        if ($pointer instanceof Carbon) {
            $pointer = $pointer->copy();
            $cacheKey = 'lc|getLinkedConnectionsInWindow|' . $pointer->getTimestamp() . '|' . $window;
            Log::info("Getting linked connectings in window with size $window for start at " . $pointer->toAtomString());
        } else {
            $cacheKey = 'lc|getLinkedConnectionsInWindow|' . $pointer . '|' . $window;
            Log::info("Getting linked connectings in window with size $window for start at " . $pointer);
        }

        if (Cache::has($cacheKey)) {
            $previousResponse = Cache::get($cacheKey);

            // If data isn't too old, just return for faster responses
            if (Carbon::now()
                ->lessThan($previousResponse->getExpiresAt())) {
                return $previousResponse;
            }
        }

        $departures = [];
        $etag = "";
        $expiresAt = null;
        $prev = null;
        $current = null;
        $next = null;

        // Compare by timestamps as departure times are also stored as timestamps
        $firstDepartureTime = 0;
        $lastDepartureTime = 0;

        while ($lastDepartureTime - $firstDepartureTime < $window) {

            $windowPage = $this->getLinkedConnections($pointer);
            $pointer = $windowPage->getNextPointer();

            $departures = array_merge($departures, $windowPage->getLinkedConnections());

            // Update the latest departure time in our results list
            if (count($departures) > 0) {
                $firstDepartureTime = $departures[0]->getDepartureTime();
                $lastDepartureTime = $departures[count($departures) - 1]->getDepartureTime();
            }

            $etag .= $windowPage->getEtag();
            //echo("Window page contains data which will expire at " .  $windowPage->getCreatedAt() . " researched for " . $pointer . PHP_EOL);

            if ($expiresAt == null || $windowPage->getExpiresAt()->lessThan($expiresAt)) {
                $expiresAt = $windowPage->getExpiresAt();
            }

            // Update variables to keep the prev and next pointers for our "larger" page
            if ($prev == null) {
                $prev = $windowPage->getPreviousPointer();
                $current = $windowPage->getCurrentPointer();
            }
            $next = $windowPage->getNextPointer();
            Log::info("Next pointer " . $next);
        }

        // Calculate a new etag based on the concatenation of al²l other etags
        $etag = md5($etag);
        /*
                if (isset($previousResponse) && $etag == $previousResponse->getEtag()) {
                    // If nothing changed, return the previous response. This way we get to keep the created_at date for caching purposes.
                    // This also means we can maybe send a 304, which will save a lot of data
                    $previousResponse->setExpiresAt($expiresAt); // Update expiration, otherwise any result containing this data will be instant invalid
                    Cache::put($cacheKey, $previousResponse, 120); // Update in cache to prevent looping through this every time again
                    return $previousResponse;
                }
        */
        $combinedPage = new LinkedConnectionPage($departures, new Carbon('UTC'), $expiresAt, $etag, $prev, $current, $next);
        Log::info("Page contains " . count($departures) . " departures");
        Cache::put($cacheKey, $combinedPage, $expiresAt);

        return $combinedPage;
    }

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime
     * @return \App\Http\Models\LinkedConnectionPage
     */
    public function getLinkedConnections($departureTime): LinkedConnectionPage
    {
        if ($departureTime instanceof Carbon) {
            $cacheKey = 'lc|getLinkedConnections|' . $departureTime->getTimestamp();
        } else {
            $cacheKey = 'lc|getLinkedConnections|' . $departureTime;
        }

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $raw = $this->rawLinkedConnectionsSource->getRawLinkedConnections($departureTime);
        $expiresAt = $raw['expiresAt'];
        $etag = $raw['etag'];
        $createdAt = $raw['createdAt'];

        $linkedConnections = [];

        foreach ($raw['data'] as $entry) {
            $linkedConnections[] = new LinkedConnection($entry);
        }
        $linkedConnectionsPage = new LinkedConnectionPage($linkedConnections, $createdAt, $expiresAt, $etag, $raw['previous'], $raw['id'], $raw['next']);

        Cache::put($cacheKey, $linkedConnectionsPage, $expiresAt);

        return $linkedConnectionsPage;
    }

    /**
     * Get the first n linked connections, starting at a certain time
     * @param \Carbon\Carbon $departureTime The departure time from where the search should start
     * @param int            $results The number of linked connections to retrieve
     * @return \App\Http\Models\LinkedConnectionPage A linkedConnections page containing all results
     */
    public    function getConnectionsByLimit(
        Carbon $departureTime,
        int $results
    ): LinkedConnectionPage
    {

        $cacheKey = 'lc|getConnectionsByLimit|' . $departureTime->getTimestamp() . "|" . $results;
        if (Cache::has($cacheKey)) {
            $previousResponse = Cache::get($cacheKey);

            // If data isn't too old, just return for faster responses
            if (Carbon::now()
                ->lessThan($previousResponse->getExpiresAt())) {
                return $previousResponse;
            }
        }

        $pointer = $departureTime;

        $departures = [];
        $etag = "";
        $expiresAt = null;

        $prev = null;
        $current = null;
        $next = null;

        while ($results < count($departures)) {
            $windowPage = $this->getLinkedConnections($pointer);
            $pointer = $windowPage->getNextPointer();

            $departures = array_merge($departures, $windowPage->getLinkedConnections());

            $etag .= $windowPage->getEtag();

            if ($expiresAt == null || $windowPage->getExpiresAt()->lessThan($expiresAt)) {
                $expiresAt = $windowPage->getExpiresAt();
            }

            if ($prev == null) {
                $prev = $windowPage->getPreviousPointer();
                $current = $windowPage->getCurrentPointer();
            }
            $next = $windowPage->getNextPointer();
        }

        // Calculate a new etag based on the concatenation of all other etags
        $etag = md5($etag);
        if (isset($previousResponse) && $etag == $previousResponse->getEtag()) {
            // Return the response with the old creation date, we can use this later on for HTTP headers
            // This also means we can maybe send a 304, which will save a lot of data
            return $previousResponse;
        }

        $combinedPage = new LinkedConnectionPage($departures, new Carbon('UTC'), $expiresAt, $etag, $prev, $current, $next);

        Cache::put($cacheKey, $combinedPage, 120);

        return $combinedPage;
    }

}
