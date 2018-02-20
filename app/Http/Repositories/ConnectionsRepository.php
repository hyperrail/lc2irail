<?php
/**
 * Created by PhpStorm.
 * User: bert
 * Date: 2/20/18
 * Time: 10:58 AM
 */

namespace App\Http\Repositories;

use Carbon\Carbon;
use irail\stations\Stations;

/**
 * Class ConnectionsRepository
 * A read-only repository for connections data
 *
 * @package App\Http\Repositories
 */
class ConnectionsRepository
{
    private const KEY_DEPARTURE_TIME = 'departure';
    private const KEY_ARRIVAL_TIME = 'arrival';
    private const KEY_DEPARTURE_CONNECTION = 'departure_connection';
    private const KEY_ARRIVAL_CONNECTION = 'arrival_connections';

    private const TIME_INFINITE = 2147483647;

    /**
     * @var LinkedConnectionsRepositoryContract
     */
    private $connectionsRepository;

    public function __construct()
    {
        $this->connectionsRepository = app(LinkedConnectionsRepository::class);
    }

    public function getConnectionsByDepartureTime($origin, $destionation, $departuretime)
    {

    }

    /**
     * @param $origin
     * @param $destination
     * @param $arrivaltime Carbon the latest arrival time
     */
    public function getConnectionsByArrivalTime($origin, $destination, Carbon $arrivaltime)
    {
        // Make a copy so we won't adjust the original variable in the calling code
        $arrivaltime = $arrivaltime->copy();

        // I want to arrive before $arrivaltime
        // See `Connection Scan Algorithm, March 20147, §4.1-4.2

        // In the following code, `2147483647` will be used to signal a point, infinitely far in time
        // TODO: This code will change behaviour when nearing Tuesday 19 January 2038 03:14:07 GMT.
        // TODO: increase this number round 2036. Increasing this number will mean PHP will interpret it as float instead of int.

        // Times are stored as timestamps (int)

        // For each stop, keep an array of (departuretime, arrivaltime) pairs
        // After execution, this array will contain the xt profile for index x
        // Size n, where n is the number of stations
        // Each entry in this array is an array of  (departuretime, arrivaltime) pairs, sorted by DESCENDING departuretime
        // A DESCENDING departurtime will ensure we always add to the back of the array, thus saving O(n) operations every time!
        // Note: for journey extraction, 2 data fields will be added. These fields can be ignored for the original Profile Connection Scan Algorithm
        $S = [];

        // For every trip, keep the earliest possible arrival time
        // The earliest arrival time for the partial journey departing in the earliest scanned connection of the corresponding trip
        // Size m, where m is the number of trips
        $T = [];

        // Initially we'll start with connections for the hour before the arrival time.
        // Don't adjust the original arrival time, we'll need it later
        $connectionsPage = $this->connectionsRepository->getLinkedConnectionsInWindow($arrivaltime->copy()->subHours(6), 21600);

        $connections = $connectionsPage->getLinkedConnections();

        for ($i = count($connections) - 1; $i >= 0; $i--) {
            $connection = $connections[$i];
            $arrivalStop = $connection->getArrivalStopUri();
            $departureStop = $connection->getDepartureStopUri();

            // Determine T1, the time when walking from here to the destination
            if ($connection->getArrivalStopUri() == $destination) {
                $T1_walking = $connection->getArrivalTime(); // TODO: take transfer walk time into consideration
            } else {
                $T1_walking = self::TIME_INFINITE; // Inter-stop walking isn't taken into consideration for now
            }

            // Determine T2, the first possible time of arrival when remaining seated
            if (key_exists($connection->getTrip(), $T)) {
                $T2_stayOnTrip = $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME];
            } else {
                $T2_stayOnTrip = self::TIME_INFINITE;
            }

            // Determine T3, the time of arrival when taking the best possible transfer in this station
            if (key_exists($arrivalStop, $S)) {

                // The earliest departure is in the back of the array
                $pairPosition = count($S[$arrivalStop]) - 1;

                $pair = $S[$arrivalStop][$pairPosition];
                $pairPosition--;

                // TODO: take delays into consideration
                // TODO: take transfer walk time into consideration
                while ($pair[self::KEY_DEPARTURE_TIME] - 240 < $connection->getArrivalTime() && $pairPosition >= 0) {
                    $pair = $S[$arrivalStop][$pairPosition];
                    $pairPosition--;
                }

                // If a result was found
                if ($pairPosition >= 0) {
                    $T3_transfer = $pair[self::KEY_ARRIVAL_TIME];
                } else {
                    $T3_transfer = self::TIME_INFINITE;
                }

            } else {
                $T3_transfer = self::TIME_INFINITE;
            }

            // Tc
            $Tmin = min($T1_walking, $T2_stayOnTrip, $T3_transfer);

            if ($Tmin == self::TIME_INFINITE) {
                continue;
            }

            // We now have the minimal arrival time for this connection

            // Update T and S with this new data
            if (key_exists($connection->getTrip(), $T)) {
                // When there is a faster way for this trip, it's by getting of at this connection's arrival station and transferring (or having arrived)
                if ($Tmin < $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME]) {
                    $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $connection];
                }
            } else {
                // To travel towards the destination, get off at the current arrival station (followed by a transfer or walk/arriving)
                $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $connection];
            }


            $pair[self::KEY_DEPARTURE_TIME] = $connection->getDepartureTime(); // TODO: take transfer walk time into consideration
            $pair[self::KEY_ARRIVAL_TIME] = $Tmin;

            // Additional data for journey extraction
            $pair[self::KEY_DEPARTURE_CONNECTION] = $connection;

            // Either we updated this
            $pair[self::KEY_ARRIVAL_CONNECTION] = $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION];

            if (key_exists($departureStop, $S)) {
                $numberOfPairs = count($S[$departureStop]);

                $q = $S[$departureStop][$numberOfPairs - 1];
                // If q does not dominate pair
                // TODO: it shouldn't be possible to find a departure time that's larger, as we're iterating over descending departure times
                if ($pair[self::KEY_ARRIVAL_TIME] < $q[self::KEY_ARRIVAL_TIME]) {
                    if ($pair[self::KEY_DEPARTURE_TIME] == $q[self::KEY_DEPARTURE_TIME]) {
                        // Replace q at the back
                        $S[$departureStop][$numberOfPairs - 1] = $pair;
                    } else {
                        // We're iterating over descending departure times, therefore the departure
                        // Insert at the back
                        $S[$departureStop][$numberOfPairs] = $pair;
                    }
                }

            } else {

                $S[$connection->getDepartureStopUri()] = [];
                $S[$connection->getDepartureStopUri()][0] = $pair;

            }

        }

        foreach ($S[$origin] as $pair) {
            echo(Carbon::createFromTimestamp($pair[self::KEY_DEPARTURE_TIME])->format('D, d M Y H:i:s e') . " - " . Carbon::createFromTimestamp($pair[self::KEY_ARRIVAL_TIME])->format('D, d M Y H:i:s e') . PHP_EOL . "<br>");
            $it = $pair;
            while ($it[self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri() != $destination) {
                $departureStation = Stations::getStationFromID($it[self::KEY_DEPARTURE_CONNECTION]->getDepartureStopUri());
                echo($it[self::KEY_DEPARTURE_CONNECTION]->getRoute() . " towards " . $it[self::KEY_DEPARTURE_CONNECTION]->getDirection() . " from " . $departureStation->name . " to ");

                $it_options = $S[$it[self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri()];
                $i = count($it_options) - 1;
                while ($i >= 0 && $it_options[$i][self::KEY_ARRIVAL_TIME] != $it[self::KEY_ARRIVAL_TIME]) {
                    $i--;
                }

                $arrivalStation = Stations::getStationFromID($it_options[$i][self::KEY_DEPARTURE_CONNECTION]->getDepartureStopUri());
                echo ($arrivalStation->name . ",<br>");

                $it = $it_options[$i];
            }

            $departureStation = Stations::getStationFromID($it[self::KEY_DEPARTURE_CONNECTION]->getDepartureStopUri());
            $arrivalStation = Stations::getStationFromID($destination);
            echo($it[self::KEY_DEPARTURE_CONNECTION]->getRoute() . " towards " . $it[self::KEY_DEPARTURE_CONNECTION]->getDirection() . " from " . $departureStation->name . " to finally arrive in " . $arrivalStation->name . "<br><br>");
        }
        return $S[$origin];

    }
}