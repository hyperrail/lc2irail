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
    const PAGE_SIZE_SECONDS = 600;
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

    public function getRawLinkedConnections(Carbon $departureTime)
    {
        $departureTime = $departureTime->copy();
        $departureTime = $this->getRoundedDepartureTime($departureTime);

        $pageCacheKey = 'lcraw|' . $departureTime->getTimestamp();

        // Page (array including json, etag and expiresAt), kept for 2 hours so we can reuse the etag
        if (false && Cache::has($pageCacheKey)) {
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
        if (!isset($raw)) {

            $scheduledBase = $this->BASE_DIRECTORY . '/linked_pages/' . $this->AGENCY;
            $realtimeBase = $this->BASE_DIRECTORY . '/real_time/' . $this->AGENCY;

            $scheduledMostRecent = array_diff(scandir($scheduledBase, SCANDIR_SORT_DESCENDING), array('..', '.'))[0];
            $realtimeMostRecent = array_diff(scandir($realtimeBase, SCANDIR_SORT_DESCENDING), array('..', '.'))[0];

            $departureTime->setTimezone('UTC');
            $scheduledFilePath = $scheduledBase . '/' . $scheduledMostRecent . '/' . date_format($departureTime, 'Y-m-d\TH:i:s.000\Z') . '.jsonld.gz';

            $realtimeFilePath = $realtimeBase . '/' . $realtimeMostRecent . '/' . array_diff(scandir($realtimeBase . '/' . $realtimeMostRecent . '/', SCANDIR_SORT_DESCENDING), array('..', '.'))[0];

            $etag = md5($scheduledFilePath . filemtime($scheduledFilePath) . filemtime($realtimeFilePath));

            // If we have a cached old value, included header for conditional get
            if (isset($previousResponse)) {
                if ($previousResponse['etag'] == $etag) {
                    //return $previousResponse;
                }
            }

            $scheduledData = [];
            foreach (json_decode('[' . gzdecode(file_get_contents($scheduledFilePath)) . ']', true) as $key => $entry) {
                if (key_exists($entry['@id'], $scheduledData)) {
                    //echo("DUPLICATE " . $entry['@id'] . "\n");
                }
                $scheduledData[$entry['@id']] = $entry;
                $scheduledData[$entry['@id']]['arrivalDelay'] = 0;
                $scheduledData[$entry['@id']]['departureDelay'] = 0;
            }

            foreach (explode('/n', file_get_contents($realtimeFilePath)) as $entry) {
                $data = json_decode($entry, true);
                $id = $data['@id'];
                if (key_exists($id, $scheduledData)) {
                    $scheduledData[$id]['arrivalDelay'] = $data['arrivalDelay'];
                    $scheduledData[$id]['departureDelay'] = $data['departureDelay'];
                }
            }

            $expiresAt = Carbon::createFromTimestamp(filemtime($realtimeFilePath) + 30);
            $raw = ['data' => array_values($scheduledData), 'etag' => $etag, 'expiresAt' => $expiresAt, 'createdAt' => new Carbon()];

            Cache::put($pageCacheKey, $raw, $expiresAt);

        }

        return $raw;
    }


    private function getRoundedDepartureTime(Carbon $departureTime): Carbon
    {
        return $departureTime->subMinute($departureTime->minute % 10)->second(0);
    }
}
