<?php

namespace App\Http\Repositories;

use App\Http\Models\IrailCarbon;
use App\Http\Models\Liveboard;
use App\Http\Models\Station;
use App\Http\Models\TrainArrival;
use App\Http\Models\TrainDeparture;
use App\Http\Models\TrainStop;
use App\Http\Models\VehicleStub;
use Carbon\Carbon;

/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class LiveboardsRepository implements LiveboardsRepositoryContract
{

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param \App\Http\Models\Station $station
     * @param Carbon                   $departureTime The time for which departures should be returned
     * @param string                   $language
     * @param int                      $window
     * @return \App\Http\Models\Liveboard
     */
    public function getDepartures(
        Station $station,
        Carbon $departureTime,
        string $language = '',
        int $window = 3600
    ): Liveboard
    {

        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $linkedConnectionsData \App\Http\Models\LinkedConnectionPage
         */
        $linkedConnectionsData = $repository->getLinkedConnectionsInWindow($departureTime, $window);
        $linkedConnections = $linkedConnectionsData->getLinkedConnections();
        //Log::info("Got " . sizeof($linkedConnections) . " departures");

        /**
         * @var $departures TrainDeparture[]
         */
        $departures = [];
        /**
         * @var $stops TrainStop[]
         */
        $stops = [];
        /**
         * @var $arrivals TrainArrival[]
         */
        $arrivals = [];

        $numberOfDepartures = 0;
        $numberOfArrivals = 0;
        $departureIdAfterThisArrival = [];

        $stationUri = $station->getUri();

        foreach ($linkedConnections as $connection) {
            if ($connection->getDepartureStopUri() == $stationUri) {
                $departures[] = new TrainDeparture(
                    $connection->getId(),
                    $connection->getDeparturePlatform(),
                    $connection->isDeparturePlatformNormal(),
                    Carbon::createFromTimestamp($connection->getDepartureTime(), "Europe/Brussels"),
                    $connection->getDepartureDelay(), $connection->isDepartureCanceled(), $connection->hasDeparted(), new VehicleStub(
                        $connection->getTrip(),
                        $connection->getRoute(),
                        $connection->getDirection()
                    )
                );
                $numberOfDepartures++;
                continue;
            }
            if ($connection->getArrivalStopUri() == $stationUri) {
                $arrivals[] = new TrainArrival(
                    $connection->getId(), $connection->getArrivalPlatform(), $connection->isArrivalPlatformNormal(), Carbon::createFromTimestamp($connection->getArrivalTime(), "Europe/Brussels"),
                    $connection->getArrivalDelay(), $connection->isArrivalCanceled(), $connection->hasArrived(), new VehicleStub(
                        $connection->getTrip(),
                        $connection->getRoute(),
                        $connection->getDirection()
                    )
                );
                $departureIdAfterThisArrival[] = $numberOfDepartures;
                $numberOfArrivals++;
                continue;
            }
        }

        $wipeArrivalIds = [];
        $wipeDepartureIds = [];

        // Departures and Arrivals are already sorted chronologically
        // We're distinguishing departures, arrivals and normal stops
        foreach ($arrivals as $id => $arrival) {
            $arrivingTrip = $arrival->getVehicle()->getId();
            $matched = false;
            $i = $departureIdAfterThisArrival[$id];
            while ($i < $numberOfDepartures && $matched == false) {
                if ($departures[$i]->getVehicle()->getId() == $arrivingTrip) {
                    $stops[] = new TrainStop($departures[$i]->getUri(),
                        $departures[$i]->getPlatform(),
                        $departures[$i]->isPlatformNormal(),
                        $arrival->getArrivalTime(),
                        $arrival->getArrivalDelay(),
                        $arrival->isArrivalCanceled(),
                        $arrival->hasArrived(),
                        $departures[$i]->getDepartureTime(),
                        $departures[$i]->getDepartureDelay(),
                        $departures[$i]->isDepartureCanceled(),
                        $departures[$i]->hasDeparted(),
                        $departures[$i]->getVehicle());

                    $wipeArrivalIds[] = $id;
                    $wipeDepartureIds[] = $i;
                    $matched = true;
                }
                $i++;
            }
        }

        foreach ($wipeDepartureIds as $id) {
            unset ($departures[$id]);
        }
        foreach ($wipeArrivalIds as $id) {
            unset ($arrivals[$id]);
        }

        $departures = array_values($departures);
        $stops = array_values($stops);
        $arrivals = array_values($arrivals);

        //Log::info("Got " . sizeof($departures) . " relevant departures");

        return new Liveboard($station, $departures, $stops, $arrivals, $linkedConnectionsData->getCreatedAt(),
            $linkedConnectionsData->getExpiresAt(), $linkedConnectionsData->getEtag());
    }
}
