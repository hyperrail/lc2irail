<?php

namespace App\Http\Repositories;

use App\Http\Models\IrailCarbon;
use App\Http\Models\LinkedConnection;
use App\Http\Models\DeparturesLiveboard;
use App\Http\Models\Station;
use App\Http\Models\TrainDeparture;
use App\Http\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class LCLiveboardsRepository
{

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param \App\Http\Models\Station $station
     * @param Carbon                   $departureTime The time for which departures should be returned
     * @param string                   $language
     * @param int                      $window
     * @return \App\Http\Models\DeparturesLiveboard
     */
    public function getDepartures(Station $station, Carbon $departureTime, string $language = '', int $window = 3600): DeparturesLiveboard
    {

        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $linkedConnections LinkedConnection[]
         */
        $linkedConnections = $repository->getLinkedConnectionsInWindow($departureTime, $window);

        Log::info("Got " . sizeof($linkedConnections) . " departures");

        $departures = [];

        foreach ($linkedConnections as $connection) {
            if ($connection->getDepartureStopUri() == $station->getUri()) {
                $departures[] = new TrainDeparture(
                    $connection->getId(),
                    new Vehicle(
                        $connection->getTrip(),
                        $connection->getTrip(),
                        "no name known",
                        new Station($connection->getArrivalStopUri(), $language)
                    ),
                    0,
                    Carbon::createFromTimestamp($connection->getDepartureTime(),"Europe/Brussels"),
                    $connection->getDepartureDelay()
                );
            }
        }
        Log::info("Got " . sizeof($departures) . " relevant departures");

        return new DeparturesLiveboard($station, $departures);
    }
}
