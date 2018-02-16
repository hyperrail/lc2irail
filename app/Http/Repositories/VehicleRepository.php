<?php

namespace App\Http\Repositories;

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
class VehicleRepository implements  VehicleRepositoryContract
{

    public function getVehicle(string $id, string $date, string $language = ''): Vehicle
    {

        $trip = "http://irail.be/vehicle/" . $id . "/" . $date;

        $datestamp = Carbon::createFromFormat("Ymd his", $date . " 030000");

        $repository = app(LinkedConnectionsRepositoryContract::class);

        /**
         * @var $stops TrainStop[]
         */
        $stops = [];
        $direction = null;
        $vehicleName = null;

        $prevArrivalTime = null;
        $prevArrivalDelay = null;
        $prevArrivalStop = null;

        $newestCreatedAt = null;
        $oldestExpiresAt = null;
        $etag = null;

        $hoursWithoutStop = 0;

        while ((count($stops) == 0 || $hoursWithoutStop < 2) && $hoursWithoutStop < 24) {

            Log::info("Getting connections for date " . $datestamp->toDateTimeString());
            /**
             * @var $linkedConnectionsData \App\Http\Models\LinkedConnectionPage
             */
            $linkedConnectionsData = $repository->getLinkedConnectionsInWindow($datestamp, 3600);
            // Increase for next query. 3600 pages are widespread used through the application, meaning more chance for it to be cached
            $datestamp->addSeconds(3600);

            if ($newestCreatedAt == null || $linkedConnectionsData->getCreatedAt()->greaterThan($newestCreatedAt)) {
                $newestCreatedAt = $linkedConnectionsData->getCreatedAt();
            }


            if ($oldestExpiresAt == null || $linkedConnectionsData->getExpiresAt()->lessThan($oldestExpiresAt)) {
                $oldestExpiresAt = $linkedConnectionsData->getCreatedAt();
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

                if (count($stops) == 0) {
                    $stops[] = new TrainDeparture(
                        $connection->getId(),
                        Carbon::createFromTimestamp($connection->getDepartureTime(), "Europe/Brussels"),
                        $connection->getDepartureDelay(), 0, null,
                        new Station($connection->getDepartureStopUri(), $language)
                    );

                    $direction = $connection->getDirection();
                    $vehicleName = $connection->getRoute();

                } else {
                    $stops[] = new TrainStop(
                        $connection->getId(),
                        0,
                        Carbon::createFromTimestamp($prevArrivalTime, "Europe/Brussels"),
                        $prevArrivalDelay,
                        Carbon::createFromTimestamp($connection->getDepartureTime(), "Europe/Brussels"),
                        $connection->getDepartureDelay(),
                        null,
                        new Station($connection->getDepartureStopUri(), $language)
                    );
                }
                Log::info("Found relevant connection " . $connection->getId());

                $prevArrivalDelay = $connection->getArrivalDelay();
                $prevArrivalTime = $connection->getArrivalTime();
                $prevArrivalStop = $connection->getArrivalStopUri();

                $hoursWithoutStop = 0;
            }

            $hoursWithoutStop++;

        }

        if ($hoursWithoutStop > 23) {
            abort(404);
        }

        $stops[] = new TrainArrival(
            "arrival",
            Carbon::createFromTimestamp($prevArrivalTime, "Europe/Brussels"),
            $prevArrivalDelay,
            0,
            null,
            new Station($prevArrivalStop, $language)
        );

        return new Vehicle($trip, $vehicleName, $direction, $stops, $newestCreatedAt,
            $oldestExpiresAt, md5($etag));
    }
}
