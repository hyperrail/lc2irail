<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnection;
use App\Http\Models\Station;
use App\Http\Models\TrainArrival;
use App\Http\Models\TrainDeparture;
use App\Http\Models\TrainStop;
use App\Http\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class VehicleRepository implements VehicleRepositoryContract
{

    public function getVehicle(string $id, string $date, string $language = ''): Vehicle
    {

        $trip = "http://irail.be/vehicle/" . $id . "/" . $date;

        $pointer = Carbon::createFromFormat("Ymd his", $date . " 030000");

        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $stops TrainStop[]
         */
        $stops = [];
        $direction = null;
        $vehicleName = null;

        $newestCreatedAt = null;
        $oldestExpiresAt = null;
        $etag = null;

        $hoursWithoutStop = 0;

        /**
         * @var $previousConnection LinkedConnection
         */
        $previousConnection = null;

        while ((count($stops) == 0 || $hoursWithoutStop < 2) && $hoursWithoutStop < 24) {

            /**
             * @var $linkedConnectionsData \App\Http\Models\LinkedConnectionPage
             */
            $linkedConnectionsData = $repository->getLinkedConnectionsInWindow($pointer, 3600);
            // Get next pointer
            $pointer = $linkedConnectionsData->getNextPointer();

            if ($newestCreatedAt == null || $linkedConnectionsData->getCreatedAt()->greaterThan($newestCreatedAt)) {
                $newestCreatedAt = $linkedConnectionsData->getCreatedAt();
            }

            if ($oldestExpiresAt == null || $linkedConnectionsData->getExpiresAt()->lessThan($oldestExpiresAt)) {
                $oldestExpiresAt = $linkedConnectionsData->getExpiresAt();
            }


            if ($etag == null) {
                $etag = $linkedConnectionsData->getEtag();
            } else {
                $etag .= $linkedConnectionsData->getEtag();
            }

            foreach ($linkedConnectionsData->getLinkedConnections() as $connection) {
                if ($connection->getTrip() != $trip) {
                    continue;
                }

                if (count($stops) === 0) {
                    $stops[] = new TrainDeparture(
                        $connection->getId(), $connection->getDeparturePlatform(), true,
                        Carbon::createFromTimestamp($connection->getDepartureTime(), "Europe/Brussels"),
                        $connection->getDepartureDelay(), $connection->isDepartureCanceled(), $connection->hasDeparted(), null,
                        new Station($connection->getDepartureStopUri(), $language)
                    );

                    $direction = $connection->getDirection();
                    $vehicleName = $connection->getRoute();
                } else {
                    $stops[] = new TrainStop(
                        $connection->getId(),
                        $connection->getDeparturePlatform(),
                        true,
                        Carbon::createFromTimestamp($previousConnection->getArrivalTime(), "Europe/Brussels"),
                        $previousConnection->getArrivalDelay(),
                        $previousConnection->isArrivalCanceled(),
                        $previousConnection->hasArrived(),
                        Carbon::createFromTimestamp($connection->getDepartureTime(), "Europe/Brussels"),
                        $connection->getDepartureDelay(),
                        $connection->isDepartureCanceled(),
                        $connection->hasDeparted(),
                        null,
                        new Station($connection->getDepartureStopUri(), $language)
                    );
                }

                Log::info("Found relevant connection " . $connection->getId());
                $previousConnection = $connection;
                $hoursWithoutStop = 0;
            }

            $hoursWithoutStop++;

        }

        if ($hoursWithoutStop > 23) {
            abort(404);
        }

        $stops[] = new TrainArrival(
            $previousConnection->getArrivalStopUri(),
            $previousConnection->getArrivalPlatform(),
            $previousConnection->isArrivalPlatformNormal(),
            Carbon::createFromTimestamp($previousConnection->getArrivalTime(), "Europe/Brussels"),
            $previousConnection->getArrivalDelay(),
            $previousConnection->isArrivalCanceled(),
            $previousConnection->hasArrived(),
            null,
            new Station($previousConnection->getArrivalStopUri(), $language)
        );

        return new Vehicle($trip, $vehicleName, $direction, $stops, $newestCreatedAt,
            $oldestExpiresAt, md5($etag));
    }
}
