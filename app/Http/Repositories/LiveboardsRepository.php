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
     * @param Carbon                   $queryTime The time for which departures should be returned
     * @param string                   $language
     * @param int                      $window
     * @return \App\Http\Models\Liveboard
     */
    public function getLiveboard(
        Station $station,
        Carbon $queryTime,
        string $language = '',
        int $window = 3600
    ): Liveboard
    {

        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $linkedConnectionsData \App\Http\Models\LinkedConnectionPage
         */
        $linkedConnectionsData = $repository->getLinkedConnectionsInWindow($queryTime, $window);
        return $this->getLiveboardData($station, $linkedConnectionsData, "liveboard.byId");
    }

    /**
     * @param Station $station
     * @param         $linkedConnectionsData
     * @return Liveboard
     */
    private function getLiveboardData(Station $station, $linkedConnectionsData, $route): Liveboard
    {
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

        $this->extractStops($arrivals, $departures, $stops, $numberOfDepartures, $departureIdAfterThisArrival);

        //Log::info("Got " . sizeof($departures) . " relevant departures");
        if (starts_with($linkedConnectionsData->getNextPointer(), "http")) {
            $prev = substr($linkedConnectionsData->getPreviousPointer(), strpos($linkedConnectionsData->getPreviousPointer(), "="));
            $next = substr($linkedConnectionsData->getNextPointer(), strpos($linkedConnectionsData->getNextPointer(), "="));
            $current = substr($linkedConnectionsData->getCurrentPointer(), strpos($linkedConnectionsData->getCurrentPointer(), "="));
        } else {
            $prev = substr($linkedConnectionsData->getPreviousPointer(), 0, -10);
            $next = substr($linkedConnectionsData->getNextPointer(), 0, -10);
            $current = substr($linkedConnectionsData->getCurrentPointer(), 0, -10);
        }

        $prev = route($route, ['timestamp' => $prev, 'id' => $station->getHid()]);
        $next = route($route, ['timestamp' => $next, 'id' => $station->getHid()]);
        $current = route($route, ['timestamp' => $current, 'id' => $station->getHid()]);

        return new Liveboard($station, $departures, $stops, $arrivals, $linkedConnectionsData->getCreatedAt(),
            $linkedConnectionsData->getExpiresAt(), $linkedConnectionsData->getEtag(), $prev, $current, $next);
    }

    /**
     * @param $arrivals
     * @param $departures
     * @param $stops
     * @param $numberOfDepartures
     * @param $departureIdAfterThisArrival
     */
    public function extractStops(&$arrivals, &$departures, &$stops, $numberOfDepartures, $departureIdAfterThisArrival): void
    {
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
    }

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param \App\Http\Models\Station $station
     * @param Carbon                   $queryTime The time for which departures should be returned
     * @param string                   $language
     * @param int                      $window
     * @return \App\Http\Models\Liveboard
     */
    public function getLiveboardBefore(
        Station $station,
        Carbon $queryTime,
        string $language = '',
        int $window = 3600
    ): Liveboard
    {

        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $linkedConnectionsData \App\Http\Models\LinkedConnectionPage
         */
        $queryTime = $queryTime->copy()->subSeconds($window);
        $linkedConnectionsData = $repository->getLinkedConnectionsInWindow($queryTime, $window);
        return $this->getLiveboardData($station, $linkedConnectionsData, "liveboard.byIdBefore");
    }
}
