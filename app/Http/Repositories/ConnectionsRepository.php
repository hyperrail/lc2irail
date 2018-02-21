<?php
/**
 * Created by PhpStorm.
 * User: bert
 * Date: 2/20/18
 * Time: 10:58 AM
 */

namespace App\Http\Repositories;

use App\Http\Models\Connection;
use App\Http\Models\ConnectionList;
use App\Http\Models\Journey;
use App\Http\Models\Station;
use Carbon\Carbon;
use irail\stations\Stations;
use SebastianBergmann\CodeCoverage\Report\PHP;

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
    public function getConnectionsByArrivalTime($origin, $destination, Carbon $arrivaltime): ConnectionList
    {
        // Make a copy so we won't adjust the original variable in the calling code
        $linkedConnectionsRetrievalTime = $arrivaltime->copy();

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

        // Keep searching until we have 5 results
        while (!key_exists($origin, $S) || count($S[$origin]) < 5) {

            // ====================================================== //
            // START GET SORTED CONNECTIONS
            // ====================================================== //

            // Initially we'll start with connections for the hour before the arrival time.
            // Don't adjust the original arrival time, we'll need it later
            $connectionsPage = $this->connectionsRepository->getLinkedConnectionsInWindow($linkedConnectionsRetrievalTime->subHours(1), 3600);
            $connections = $connectionsPage->getLinkedConnections();

            // ====================================================== //
            // END GET SORTED CONNECTIONS
            // ====================================================== //

            // Loop over connections by descending departure time
            for ($i = count($connections) - 1; $i >= 0; $i--) {

                // The connection we're scanning at this moment
                $connection = $connections[$i];

                if ($connection->getArrivalTime() > $arrivaltime->getTimestamp()){
                    // If this connection arrives after the arrival time the user specified, skip it.
                    continue;
                }

                // The arrival stop of this connection. For more readable code.
                $arrivalStop = $connection->getArrivalStopUri();
                // The departure stop of this connection. For more readable code.
                $departureStop = $connection->getDepartureStopUri();

                // ====================================================== //
                // START GET EARLIEST ARRIVAL TIME
                // ====================================================== //

                // Determine T1, the time when walking from here to the destination
                if ($connection->getArrivalStopUri() == $destination) {
                    // If this connection ends at the destination, we can walk from here to tthe station exit.
                    // Our implementation does not add a footpath at the end
                    // Therefore, we arrive at our destination at the time this connection arrives
                    $T1_walking = $connection->getArrivalTime();
                } else {
                    // When this isn't the destination stop, we would arrive somewhere far, far in the future.
                    // We're walking infinitly slow: we prefer a train
                    // For stops which are close to each other, we could walk to another stop to take a train there
                    // This is to be supported later on, but requires a list of footpaths.
                    // TODO: support walking to a nearby stop, e.g. haren/haren-zuid
                    $T1_walking = self::TIME_INFINITE;
                }

                // Determine T2, the first possible time of arrival when remaining seated
                if (key_exists($connection->getTrip(), $T)) {
                    // When we remain seated on this train, we will arrive at the fastest arrival time possible for this vehicle
                    $T2_stayOnTrip = $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME];
                } else {
                    // When there isn't a fastest arrival time for this stop yet, it means we haven't found a connection
                    // - To arrive in the destination using this vehicle, or
                    // - To transfer to another vehicle in another station
                    $T2_stayOnTrip = self::TIME_INFINITE;
                }

                // Determine T3, the time of arrival when taking the best possible transfer in this station
                if (key_exists($arrivalStop, $S)) {
                    // If there are connections leaving from the arrival station, determine the one which departs after we arrive,
                    // but arrives as soon as possible

                    // The earliest departure is in the back of the array. This int will keep track of which pair we're evaluating.
                    $pairPosition = count($S[$arrivalStop]) - 1;

                    $pair = $S[$arrivalStop][$pairPosition];
                    $pairPosition--;

                    // TODO: replace hard-coded transfer time
                    // As long as we're arriving AFTER the pair departure, move forward in the list until we find a departure which is reachable
                    // The list is sorted by descending departure time, so the earliest departures are in the back (so we move back to front)
                    while ($pair[self::KEY_DEPARTURE_TIME] - 240 < $connection->getDelayedArrivalTime() && $pairPosition >= 0) {
                        $pair = $S[$arrivalStop][$pairPosition];
                        $pairPosition--;
                    }

                    if ($pairPosition >= 0) {
                        // If a result was found in the list, this is the earliest arrival time when transferring here
                        $T3_transfer = $pair[self::KEY_ARRIVAL_TIME];
                    } else {
                        // When there isn't a reachable connection, transferring isn't an option
                        $T3_transfer = self::TIME_INFINITE;
                    }

                } else {
                    // When there isn't a reachable connection, transferring isn't an option
                    $T3_transfer = self::TIME_INFINITE;
                }

                // Tc in the pahper
                // This is the earliest arrival time over the 3 possibilities
                $Tmin = min($T1_walking, $T2_stayOnTrip, $T3_transfer);

                // ====================================================== //
                // END GET EARLIEST ARRIVAL TIME
                // ====================================================== //

                if ($Tmin == self::TIME_INFINITE) {
                    // If we haven't found an arrival time, just keep scanning
                    // We need to come across connections which halt in the destination first
                    continue;
                }

                // We now have the minimal arrival time for this connection
                // Update T and S with this new data

                // ====================================================== //
                // START UPDATE T
                // ====================================================== //

                // Where do we need to get off the train?
                // The following if-else structure does not follow the Journey Extraction algorithm as described in the CSA (march 2017) paper.
                // Not only do we want to reconstruct the journey (the vehicles used), but we want departure and arrival times for every single leg.
                // In order to also have the arrival times, we will always save the arrival connection for the next hop, instead of the arrival connection for the entire journey.
                if ($Tmin == $T1_walking) {
                    // We're walking from here, so get off here
                    $exitTrainConnection = $connection;
                } else if ($Tmin == $T2_stayOnTrip) {
                    // We're staying on this trip. This also implicates a key in $T exists for this trip. We're getting off at the previous exit for this vehicle.
                    $exitTrainConnection = $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION];
                } else {
                    // $Tmin == $T3_transfer
                    // We're transferring here, so get off the train in this station
                    $exitTrainConnection = $connection;
                }

                // Set the fastest arrival time for this vehicle, and set the connection at which we have to hop off
                if (key_exists($connection->getTrip(), $T)) {
                    // When there is a faster way for this trip, it's by getting of at this connection's arrival station and transferring (or having arrived)
                    if ($Tmin < $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME]) {
                        $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $exitTrainConnection];
                    }
                } else {
                    // To travel towards the destination, get off at the current arrival station (followed by a transfer or walk/arriving)
                    $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $exitTrainConnection];
                }

                // ====================================================== //
                // END UPDATE T
                // ====================================================== //

                // ====================================================== //
                // START UPDATE S
                // ====================================================== //

                // Create a quadruple to update S
                $quad[self::KEY_DEPARTURE_TIME] = $connection->getDelayedDepartureTime(); // TODO: take transfer walk time into consideration
                $quad[self::KEY_ARRIVAL_TIME] = $Tmin;

                // Additional data for journey extraction
                $quad[self::KEY_DEPARTURE_CONNECTION] = $connection;
                $quad[self::KEY_ARRIVAL_CONNECTION] = $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION];

                if (key_exists($departureStop, $S)) {
                    $numberOfPairs = count($S[$departureStop]);

                    $q = $S[$departureStop][$numberOfPairs - 1];
                    // If q does not dominate pair
                    // TODO: it shouldn't be possible to find a departure time that's larger, as we're iterating over descending departure times
                    if ($quad[self::KEY_ARRIVAL_TIME] < $q[self::KEY_ARRIVAL_TIME]) {
                        if ($quad[self::KEY_DEPARTURE_TIME] == $q[self::KEY_DEPARTURE_TIME]) {
                            // Replace q at the back
                            $S[$departureStop][$numberOfPairs - 1] = $quad;
                        } else {
                            // We're iterating over descending departure times, therefore the departure
                            // Insert at the back
                            $S[$departureStop][$numberOfPairs] = $quad;
                        }
                    }

                } else {

                    $S[$connection->getDepartureStopUri()] = [];
                    $S[$connection->getDepartureStopUri()][0] = $quad;

                }

                // ====================================================== //
                // END UPDATE S
                // ====================================================== //
            }
        }

        $results = [];

        if (!key_exists($origin, $S)) {
            abort(404, "No routes found");
        }

        foreach ($S[$origin] as $quad) {
            // $it will iterate over all the legs (journeys) in a connection
            $it = $quad;

            $journeys = [];

            // As long as $it doesn't contain the leg (journey) which arrives at our destination, keep searching.
            // Footpaths to walk to the destination aren't supported, therefore this is a valid check.
            while ($it[self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri() != $destination) {
                $it_options = $S[$it[self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri()];
                $i = count($it_options) - 1;
                // Find the next hop. This is the first reachable hop,
                // or even stricter defined: the hop which will get us to the destination at the same arrival time.
                while ($i >= 0 && $it_options[$i][self::KEY_ARRIVAL_TIME] != $it[self::KEY_ARRIVAL_TIME]) {
                    $i--;
                }
                $journeys[] = new Journey($it[self::KEY_DEPARTURE_CONNECTION], $it[self::KEY_ARRIVAL_CONNECTION]);
                $it = $it_options[$i];
            }

            // Store the last leg
            $journeys[] = new Journey($it[self::KEY_DEPARTURE_CONNECTION], $it[self::KEY_ARRIVAL_CONNECTION]);

            // Store the entire connection
            $results[] = new Connection($journeys);
        }

        // Store and return the list of connections
        return new ConnectionList(new Station($origin), new Station($destination), $results, new Carbon(), Carbon::now()->addMinute(), md5(json_encode($results)));

    }
}