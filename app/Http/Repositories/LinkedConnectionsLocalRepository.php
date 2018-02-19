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
        if ($pointer instanceof Carbon) {
            return $this->getRawLinkedConnectionsByDateTime($pointer);
        } else {
            throw new \InvalidArgumentException("Invalid argument type");
        }
    }

    private function getRawLinkedConnectionsByDateTime(Carbon $departureTime)
    {
        $departureTime = $departureTime->copy();
        $departureTime = $this->getRoundedDepartureTime($departureTime);
        $pageCacheKey = 'lc|getRawLinkedConnections|' . $departureTime->getTimestamp();

        if (Cache::has($pageCacheKey)) {
            return Cache::get($pageCacheKey);
        }


        $departureTime->setTimezone('UTC');
        $scheduledBase = $this->BASE_DIRECTORY . '/linked_pages/' . $this->AGENCY;
        $realtimeBase = $this->BASE_DIRECTORY . '/real_time/' . $this->AGENCY;

        $scheduledMostRecent = array_diff(scandir($scheduledBase, SCANDIR_SORT_DESCENDING), array('..', '.'))[0];

        // TODO: check if this behaviour is correct when there are multiple folders
        // TODO: what should be done while the generation is running (old complete data and new incomplete data available)
        $scheduledFilePath = $scheduledBase . '/' . $scheduledMostRecent . '/' . date_format($departureTime, 'Y-m-d\TH:i:s.000\Z') . '.jsonld.gz';

        // Hacky date_format thingy to remove a leading zero in a month
        $realtimeDataCompressed = false;
        $realtimeFilePath = $realtimeBase . '/' . date_format($departureTime, 'Y_') . (int)date_format($departureTime, 'm') . date_format($departureTime, '_d') . '/' . date_format($departureTime, 'Y-m-d\TH:i:s.000\Z') . '.jsonld';
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
        if ($departureTime->lessThan(Carbon::now()->subMinutes(120))) {
            $expiresAt = Carbon::now('UTC')->addHours(1);
        } else {
            $expiresAt = Carbon::now('UTC')->addSeconds(30);
        }

        if (file_exists($scheduledFilePath)) {
            foreach (json_decode('[' . gzdecode(file_get_contents($scheduledFilePath)) . ']', true) as $key => $entry) {
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

        $next = $departureTime->copy()->addMinutes(self::PAGE_SIZE_MINUTES);
        $previous = $departureTime->copy()->subMinutes(self::PAGE_SIZE_MINUTES);

        $raw = ['data' => array_values($departures), 'etag' => $etag, 'expiresAt' => $expiresAt, 'createdAt' => new Carbon('UTC'), 'next' => $next, 'previous' => $previous];

        Cache::put($pageCacheKey, $raw, $expiresAt);

        return $raw;
    }


    private function getRoundedDepartureTime(Carbon $departureTime): Carbon
    {
        return $departureTime->subMinute($departureTime->minute % 10)->second(0);
    }
}
