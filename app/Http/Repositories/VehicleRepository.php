<?php

namespace App\Http\Repositories;

use App\Http\Models\TrainStop;
use App\Http\Models\Vehicle;
use Carbon\Carbon;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class VehicleRepository implements  VehicleRepositoryContract
{

    public function getVehicle(string $trip, string $date, string $language = ''): Vehicle
    {

        $datestamp = Carbon::createFromFormat("Ymd his",$date . " 020000");

        $vehicleName = "IC000"; // TODO: fix
        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $linkedConnectionsData \App\Http\Models\LinkedConnectionPage
         */
        $linkedConnectionsData = $repository->getLinkedConnectionsInWindow($datestamp, 86400);
        $linkedConnections = $linkedConnectionsData->getLinkedConnections();

        //Log::info("Got " . sizeof($linkedConnections) . " departures");

        /**
         * @var $stops TrainStop[]
         */
        $stops = [];
        $direction = null;
        // TODO: use a while loop here to check if more pages should be retrieved
        foreach ($linkedConnections as $connection) {
            if ($connection->getTrip() == $trip) {
                $stops[] = new TrainStop(
                    $connection->getId(),
                    0,
                    Carbon::createFromTimestamp($connection->getArrivalTime(),"Europe/Brussels"),
                    $connection->getArrivalDelay(),
                    Carbon::createFromTimestamp($connection->getDepartureTime(),"Europe/Brussels"),
                    $connection->getDepartureDelay()
                );
                $direction = $connection->getArrivalStopUri();
            }
        }

        return new Vehicle($trip,$vehicleName, 'dir ' . $direction, $stops, $linkedConnectionsData->getCreatedAt(),
            $linkedConnectionsData->getExpiresAt(), $linkedConnectionsData->getEtag());
    }
}
