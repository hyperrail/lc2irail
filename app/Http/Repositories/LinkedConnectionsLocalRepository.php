<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnectionPage;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Models\LinkedConnection;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format, based on locally stored linkedconnections data.
 *
 * @package App\Http\Controllers
 */
class LinkedConnectionsLocalRepository implements LinkedConnectionsRawRepositoryContract
{

    // Example file name: /linked_pages/sncb/2018-01-20T04:10:32.000Z/2018-12-09T00:30:00.000Z.jsonld.gz
    // /linked_pages/agency/date_retrieved/query_date.jsonld.gz

    // Example realtime data: /real_time/sncb/2018_2_14/2018-12-09T00:30:00.000Z.jsonld.gz

    var $BASE_DIRECTORY;
    const PAGE_SIZE_MINUTES = 10;
    private $AGENCY;

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->BASE_DIRECTORY = env("LINKED_CONNECTIONS_BASE_DIR", "/home/vagrant/linked-connections-server/storage");
        $this->AGENCY = env("LINKED_CONNECTIONS_AGENCY", "sncb");
    }

    public function getRawLinkedConnections($pointer)
    {
        if (is_string($pointer)){
            return $this->getRawLinkedConnectionsByString($pointer);
        } elseif ( $pointer instanceof Carbon) {
            return $this->getRawLinkedConnectionsByDateTime($pointer);
        } else {
            throw new \InvalidArgumentException("Invalid argument type");
        }
    }

    private function getRawLinkedConnectionsByDateTime(Carbon $departureTime)
    {
        $departureTime = $departureTime->copy();
        $departureTime->setTimezone('UTC');
        return $this->getRawLinkedConnectionsByString(date_format($departureTime, 'Y-m-d\TH:i:s.000\Z') . '.jsonld.gz');
    }

    private function getRawLinkedConnectionsByString(string $filename)
    {
        $pageCacheKey = 'lc|getRawLinkedConnections|' . $filename;

        if (Cache::has($pageCacheKey)) {
            return Cache::get($pageCacheKey);
        }

        $scheduledBase = $this->BASE_DIRECTORY . '/linked_pages/' . $this->AGENCY;
        $realtimeBase = $this->BASE_DIRECTORY . '/real_time/' . $this->AGENCY;

        $mostRecentDataVersion = array_diff(scandir($scheduledBase, SCANDIR_SORT_DESCENDING), ['..', '.'])[0];
        $scheduledDataFragments = array_diff(scandir($scheduledBase . '/' . $mostRecentDataVersion . '/', SCANDIR_SORT_ASCENDING), ['..', '.']);

        $scheduledFilePath = $scheduledBase . '/' . $mostRecentDataVersion . '/' . $filename ;

        $existingFiles = $this->binarySearchFirstSmallerThan($scheduledDataFragments, basename($scheduledFilePath));

        $previous = $existingFiles[0];
        $next = $existingFiles[2];

        $scheduledFilePath = $scheduledBase . '/' . $mostRecentDataVersion . '/' . $existingFiles[1];

        Log::info($scheduledFilePath . " is first smaller");

        // TODO: check if this behaviour is correct when there are multiple folders
        // TODO: what should be done while the generation is running (old complete data and new incomplete data available)


        $realtimeDataCompressed = false;
        // Hacky date_format thingy to remove a leading zero in a month

        $realtimeFilePath = $realtimeBase . '/' . $mostRecentDataVersion . '/' . 'dateprefix' . '/' . substr($filename,0,-3);
        if (!file_exists($realtimeFilePath)) {
            $realtimeDataCompressed = true;
            $realtimeFilePath .= ".gz";
        }

        $scheduledModified = 0;
        if (file_exists($scheduledFilePath)) {
            $scheduledModified = filemtime($scheduledFilePath);
        }

        $realtimeModified = 0;
        if (file_exists($realtimeFilePath)) {
            $realtimeModified = filemtime($realtimeFilePath);
        }

        $etag = md5($scheduledFilePath . $scheduledModified . $realtimeModified);

        $departures = [];
        // Data which is more than 2 hours old can be cached for 1 hour
        // TODO: research exact time after which data never changes (and expiration can be set to one hour or longer)
        $expiresAt = Carbon::now('UTC')->addSeconds(30);

        if (file_exists($scheduledFilePath)) {
            $data = file_get_contents($scheduledFilePath);
            $data = gzdecode($data);


            foreach (json_decode('[' . $data . ']', true) as $key => $entry) {
                if ($entry["gtfs:pickupType"] != "gtfs:Regular" || $entry["gtfs:dropOffType"] != "gtfs:Regular") {
                    continue;
                }

                $departures[$entry['@id']] = $entry;
                $departures[$entry['@id']]['arrivalDelay'] = 0;
                $departures[$entry['@id']]['departureDelay'] = 0;
            }
        }

        if (file_exists($realtimeFilePath)) {
            // Decode realtime data if necessary
            $realtimeContents = file_get_contents($realtimeFilePath);
            if ($realtimeDataCompressed) {
                $realtimeContents = gzdecode($realtimeContents);
            }

            foreach (explode(PHP_EOL, $realtimeContents) as $entry) {
                $data = json_decode($entry, true);
                $id = $data['@id'];

                // Overwrite existing, add new
                $departures[$id] = $data;
            }
        }


        $raw = ['data' => array_values($departures), 'etag' => $etag, 'expiresAt' => $expiresAt, 'createdAt' => new Carbon('UTC'), 'next' => $next, 'previous' => $previous];

        Cache::put($pageCacheKey, $raw, $expiresAt);

        return $raw;
    }


    /**
     * @param array  $haystack Array sorted by ascending values
     * @param string $needle The needle to search for
     * @return string An array including the previous result, (The first smaller value, or the exact value), and the next result
     */
    private function binarySearchFirstSmallerThan(array $haystack, string $needle): array
    {
        $haystack = array_values($haystack);

        $l = 0;
        $length = count($haystack);
        $r = $length;

        // start in the middle
        $position = (int) floor($r / 2);
        Log::info("Searching $needle, start at $position");

        $iterations = 0;
        while ($position + 1 < $length && $l < $r && !($needle >= $haystack[$position] && $needle < $haystack[$position + 1])) {
            if ($needle < $haystack[$position]) {
                $r = $position;
                Log::info("Limiting right to $position");
            }
            if ($needle > $haystack[$position + 1]) {
                $l = $position + 1;
                Log::info("Limiting left to " . ($position + 1));
            }

            //Log::info("Left {$haystack[$l]} Right {$haystack[$r]}");
            $newposition = floor($l + ($r - $l) / 2);
            if ($position == $newposition){
                $position = $newposition + 1;
            } else {
                $position = $newposition;
            }
            $iterations++;
        }

        Log::info("Found " . $haystack[$position] . " in $iterations iterations");

        $result = [];
        if ($position > 0) {
            $result[0] = $haystack[$position - 1];
        } else {
            $result[0] = null;
        }

        $result[1] = $haystack[$position];
        if ($position > 0) {
            $result[2] = $haystack[$position + 1];
        } else {
            $result[2] = null;
        }
        return $result;
    }
}
