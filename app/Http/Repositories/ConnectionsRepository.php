<?php

namespace App\Http\Repositories;

use App\Http\Models\Connection;
use App\Http\Models\ConnectionList;
use App\Http\Models\JourneyLeg;
use App\Http\Models\Station;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
    private const KEY_ARRIVAL_CONNECTION = 'arrival_connection';
    private const KEY_TRANSFER_COUNT = 'transfers';

    private const TIME_INFINITE = 2147483647;
    const TransferEquivalentTravelTime = 240;
    const IntraStopFootpathTime = 300;

    /**
     * @var LinkedConnectionsRepositoryContract
     */
    private $connectionsRepository;

    public function __construct()
    {
        $this->connectionsRepository = app(LinkedConnectionsRepository::class);
    }

    public function getConnectionsByDepartureTime($origin, $destination, $departuretime, $language, $results = 8): ConnectionList
    {
        // By not passing an arrival time, getConnections will determine set a good value to start scanning
        return $this->getConnections($origin, $destination, $departuretime, null, 10, $results, $language);
    }

    /**
     * @param $origin
     * @param $destination
     * @param $arrivaltime Carbon the latest arrival time
     * @param $language
     * @return ConnectionList
     */
    public function getConnectionsByArrivalTime($origin, $destination, Carbon $arrivaltime, $language, $results = 8): ConnectionList
    {
        return $this->getConnections($origin, $destination, null, $arrivaltime, 10, $results, $language);
    }

    public function getConnections($origin, $destination, Carbon $departureTime = null, Carbon $arrivaltime = null, $maxTransfers = 10, $resultCount = 8, $language = 'en')
    {
        if ($arrivaltime == null) {
            if ($departureTime == null) {
                $departureTime = new Carbon();
            }
            $arrivaltime = $departureTime->copy()->addHours(8);
        }

        // For caching purposes
        $expiresAt = null;
        $etag = "";

        // Make a copy so we won't adjust the original variable in the calling code
        $pointer = $arrivaltime->copy();

        // We'll use this variable to detect whether or not we should stop because we've passed the request departure time
        $departureTimeHasBeenPassed = false;

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

        // Keep searching
        // - while no results have been found
        // - until we have the number of results we'd like (in case no departure time is given)
        // - but stop when we're passing the departe time limit
        // - when we're searching with a departuretime, we need to continue until we're at the front. This might result in more results, which we'll all pass to the client
        while (
            (
                !key_exists($origin, $S) ||
                count($S[$origin]) < $resultCount ||
                $departureTime != null
            ) &&
            (
                $departureTime == null || !$departureTimeHasBeenPassed)
        ) {

            // ====================================================== //
            // START GET SORTED CONNECTIONS
            // ====================================================== //

            // Initially we'll start with connections for the hour before the arrival time.
            // Don't adjust the original arrival time, we'll need it later
            $connectionsPage = $this->connectionsRepository->getLinkedConnections($pointer);

            // We will loop over the pages in descending order
            $pointer = $connectionsPage->getPreviousPointer();
            $connections = $connectionsPage->getLinkedConnections();

            // If expiresAt isn't set or if the expiration date for this page is earlier than the current page
            if ($expiresAt == null || $connectionsPage->getExpiresAt() < $expiresAt) {
                $expiresAt = $connectionsPage->getExpiresAt();
            }
            $etag .= $connectionsPage->getEtag();

            // ====================================================== //
            // END GET SORTED CONNECTIONS
            // ====================================================== //

            // Loop over connections by descending departure time
            for ($i = count($connections) - 1; $i >= 0; $i--) {

                // The connection we're scanning at this moment
                $connection = $connections[$i];

                // Detect if we're past the requested arrival time
                if ($connection->getArrivalTime() > $arrivaltime->getTimestamp()) {
                    // If this connection arrives after the arrival time the user specified, skip it.
                    continue;
                }

                // Detect if we're past the requested departure time
                if ($departureTime != null && $connection->getDepartureTime() < $departureTime->getTimestamp()) {
                    $departureTimeHasBeenPassed = true;
                    // If this connection departs before the departure time the user specified, skip it.
                    continue;
                }

                // The arrival stop of this connection. For more readable code.
                $arrivalStop = $connection->getArrivalStopUri();
                // The departure stop of this connection. For more readable code.
                $departureStop = $connection->getDepartureStopUri();

                // ====================================================== //
                // START GET EARLIEST ARRIVAL TIME
                // ====================================================== //

                // Log::info((new Station($connection->getDepartureStopUri()))->getDefaultName() .' - '.(new Station($connection->getArrivalStopUri()))->getDefaultName() .' - '. $connection->getRoute());

                // Determine T1, the time when walking from here to the destination
                if ($connection->getArrivalStopUri() == $destination) {
                    // If this connection ends at the destination, we can walk from here to tthe station exit.
                    // Our implementation does not add a footpath at the end
                    // Therefore, we arrive at our destination at the time this connection arrives
                    $T1_walkingArrivalTime = $connection->getArrivalTime();

                    // We're walking, so this connections has no transfers between it and the destination
                    $T1_transfers = 0;
                    // Log::info("[{$connection->getId()}] Walking possible with arrival time  $T1_walkingArrivalTime.");
                } else {
                    // When this isn't the destination stop, we would arrive somewhere far, far in the future.
                    // We're walking infinitly slow: we prefer a train
                    // For stops which are close to each other, we could walk to another stop to take a train there
                    // This is to be supported later on, but requires a list of footpaths.
                    // TODO: support walking to a nearby stop, e.g. haren/haren-zuid
                    $T1_walkingArrivalTime = self::TIME_INFINITE;
                    // Default value to prevent errors due to undefined variables.
                    // Will never be used: when an infinitely late arrival is to earliest available, the for loop will skip to the next connection.
                    $T1_transfers = false;
                    // Log::info("[{$connection->getId()}] Walking not possible.");
                }

                // Determine T2, the first possible time of arrival when remaining seated
                if (key_exists($connection->getTrip(), $T)) {
                    // When we remain seated on this train, we will arrive at the fastest arrival time possible for this vehicle
                    $T2_stayOnTripArrivalTime = $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME];
                    // Remaining seated will have the same number of transfers between this connection and the destination, as from the best exit stop and the destination
                    $T2_transfers = $T[$connection->getTrip()][self::KEY_TRANSFER_COUNT];
                    // Log::info("[{$connection->getId()}] Remaining seated possible with arrival time $T2_stayOnTripArrivalTime and $T2_transfers transfers.");
                } else {
                    // When there isn't a fastest arrival time for this stop yet, it means we haven't found a connection
                    // - To arrive in the destination using this vehicle, or
                    // - To transfer to another vehicle in another station
                    $T2_stayOnTripArrivalTime = self::TIME_INFINITE;
                    // Default value to prevent errors due to undefined variables.
                    // Will never be used: when an infinitely late arrival is to earliest available, the for loop will skip to the next connection.
                    $T2_transfers = false;
                    // Log::info("[{$connection->getId()}] Remaining seated not possible");
                }

                // Determine T3, the time of arrival when taking the best possible transfer in this station

                if (key_exists($arrivalStop, $S)) {
                    // If there are connections leaving from the arrival station, determine the one which departs after we arrive,
                    // but arrives as soon as possible

                    // The earliest departure is in the back of the array. This int will keep track of which pair we're evaluating.
                    $pairPosition = count($S[$arrivalStop]) - 1;

                    $pair = $S[$arrivalStop][$pairPosition];

                    // TODO: replace hard-coded transfer time
                    // As long as we're arriving AFTER the pair departure, move forward in the list until we find a departure which is reachable
                    // The list is sorted by descending departure time, so the earliest departures are in the back (so we move back to front)
                    while (($pair[self::KEY_DEPARTURE_TIME] - self::IntraStopFootpathTime < $connection->getArrivalTime() || $pair[self::KEY_TRANSFER_COUNT] >= $maxTransfers) && $pairPosition > 0) {
                        $pairPosition--;
                        $pair = $S[$arrivalStop][$pairPosition];
                    }

                    if ($pair[self::KEY_DEPARTURE_TIME] - self::IntraStopFootpathTime >= $connection->getArrivalTime() && $pair[self::KEY_TRANSFER_COUNT] <= $maxTransfers) {
                        // If a result was found in the list, this is the earliest arrival time when transferring here
                        // Optional: Adding one second to the arrival time will ensure that the route with the smallest number of legs is chosen.
                        // This would not affect journey extaction, but would prefer routes with less legs when arrival times are identical (as their arrival time will be one second earlier)
                        // It would prefer remaining seated over transferring when both would result in the same arrival time
                        // See http://lc2irail.dev/connections/008822160/008895257/departing/1519924311
                        $T3_transferArrivalTime = $pair[self::KEY_ARRIVAL_TIME] + self::TransferEquivalentTravelTime;

                        // Using this transfer will increase the number of transfers with 1
                        $T3_transfers = $pair[self::KEY_TRANSFER_COUNT] + 1;

                        // $transferTime = $pair[self::KEY_DEPARTURE_TIME] - $connection->getArrivalTime();
                        // Log::info("[{$connection->getId()}] Transferring possible with arrival time $T3_transferArrivalTime and $T3_transfers transfers. Transfer time is $transferTime.");
                    } else {

                        // When there isn't a reachable connection, transferring isn't an option
                        $T3_transferArrivalTime = self::TIME_INFINITE;
                        // Default value to prevent errors due to undefined variables.
                        // Will never be used: when an infinitely late arrival is to earliest available, the for loop will skip to the next connection.
                        $T3_transfers = false;
                        // Log::info("[{$connection->getId()}] Transferring not possible: No transfers reachable");
                    }

                } else {
                    // When there isn't a reachable connection, transferring isn't an option
                    $T3_transferArrivalTime = self::TIME_INFINITE;
                    // Default value to prevent errors due to undefined variables.
                    // Will never be used: when an infinitely late arrival is to earliest available, the for loop will skip to the next connection.
                    $T3_transfers = false;
                    // Log::info("[{$connection->getId()}] Transferring not possible: No transfers exist");
                }

                // Tmin = Tc in the paper
                // This is the earliest arrival time over the 3 possibilities

                // Where do we need to get off the train?
                // The following if-else structure does not follow the JourneyLeg Extraction algorithm as described in the CSA (march 2017) paper.
                // Not only do we want to reconstruct the journey (the vehicles used), but we want departure and arrival times for every single leg.
                // In order to also have the arrival times, we will always save the arrival connection for the next hop, instead of the arrival connection for the entire journey.

                // If T3 < T2, prefer a transfer. If T2 <= T3, prefer remaining seated.
                // Here we force the least amount of transfers for the same arrival time
                // TODO: here we could also apply "3 minutes longer travel for one less arrival"
                if ($T3_transferArrivalTime <= $T2_stayOnTripArrivalTime) {
                    // Log::info("Transfer time!");
                    $Tmin = $T3_transferArrivalTime;

                    // We're transferring here, so get off the train in this station
                    $exitTrainConnection = $connection;

                    // We already incremented this transfer counter when determining the train
                    $numberOfTransfers = $T3_transfers;
                } else {
                    // Log::info("Train time!");
                    $Tmin = $T2_stayOnTripArrivalTime;

                    // We're staying on this trip. This also implicates a key in $T exists for this trip. We're getting off at the previous exit for this vehicle.
                    if ($T2_stayOnTripArrivalTime < self::TIME_INFINITE) {
                        $exitTrainConnection = $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION];
                    }
                    $numberOfTransfers = $T2_transfers;
                }

                // For equal times, we prefer just arriving.
                if ($T1_walkingArrivalTime <= $Tmin) {
                    // Log::info("Nvm, walking time!");
                    $Tmin = $T1_walkingArrivalTime;

                    // We're walking from here, so get off here
                    $exitTrainConnection = $connection;
                    $numberOfTransfers = $T1_transfers;
                }

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

                // Set the fastest arrival time for this vehicle, and set the connection at which we have to hop off
                if (key_exists($connection->getTrip(), $T)) {
                    // When there is a faster way for this trip, it's by getting of at this connection's arrival station and transferring (or having arrived)

                    // Can also be equal for a transfer with the best transfer (don't do bru south - central - north - transfer - north - central - south
                    // We're updating an existing connection, with a way to get off earlier (iterating using descending departure times).
                    // This only modifies the transfer stop, nothing else in the journey
                    if ($Tmin == $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME]
                        && $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri() != $destination
                        && $T3_transferArrivalTime == $T2_stayOnTripArrivalTime
                        && key_exists($T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri(), $S)
                        && key_exists($connection->getArrivalStopUri(), $S)
                    ) {
                        // When the arrival time is the same, the number of transfers should also be the same
                        // We prefer the exit connection with the largest transfer time

                        // Suppose we exit the train here: $connection. Does this improve on the transfer time?
                        $currentTrainExit = $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION];

                        // Now we need the departure in the next station!

                        // Create a quadruple to lookup the first reachable connection in S
                        // Create one, because we don't know where we'd get on this train
                        $quad[self::KEY_DEPARTURE_TIME] = null;
                        $quad[self::KEY_DEPARTURE_CONNECTION] = $connection;

                        // Current situation
                        $quad[self::KEY_ARRIVAL_TIME] = $Tmin;
                        $quad[self::KEY_ARRIVAL_CONNECTION] = $currentTrainExit;

                        $currentTransfer = $this->getFirstReachableConnection($S, $quad)[self::KEY_DEPARTURE_TIME] - $currentTrainExit->getArrivalTime();

                        // New situation
                        $quad[self::KEY_ARRIVAL_TIME] = $Tmin;
                        $quad[self::KEY_ARRIVAL_CONNECTION] = $exitTrainConnection;

                        $newTransfer = $this->getFirstReachableConnection($S, $quad)[self::KEY_DEPARTURE_TIME] - $exitTrainConnection->getArrivalTime();

                        // If the new situation is better
                        if ($newTransfer > $currentTransfer) {
                            $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $exitTrainConnection, self::KEY_TRANSFER_COUNT => $numberOfTransfers];
                        }


                    }

                    if ($Tmin < $T[$connection->getTrip()][self::KEY_ARRIVAL_TIME]) {
                        // $exit = (new Station($exitTrainConnection->getArrivalStopUri()))->getDefaultName();
                        // Log::info("[{$connection->getId()}] Updating T: Arrive at $Tmin using {$connection->getRoute()} with $numberOfTransfers transfers. Get off at {$exit}.");
                        $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $exitTrainConnection, self::KEY_TRANSFER_COUNT => $numberOfTransfers];
                    }
                } else {
                    // $exit = (new Station($exitTrainConnection->getArrivalStopUri()))->getDefaultName();
                    // Log::info("[{$connection->getId()}] Updating T: New: Arrive at $Tmin using {$connection->getRoute()} with $numberOfTransfers transfers. Get off at {$exit}.");
                    // To travel towards the destination, get off at the current arrival station (followed by a transfer or walk/arriving)
                    $T[$connection->getTrip()] = [self::KEY_ARRIVAL_TIME => $Tmin, self::KEY_ARRIVAL_CONNECTION => $exitTrainConnection, self::KEY_TRANSFER_COUNT => $numberOfTransfers];
                }

                // ====================================================== //
                // END UPDATE T
                // ====================================================== //

                // ====================================================== //
                // START UPDATE S
                // ====================================================== //

                // Create a quadruple to update S
                $quad[self::KEY_DEPARTURE_TIME] = $connection->getDepartureTime();
                $quad[self::KEY_ARRIVAL_TIME] = $Tmin;

                // Additional data for journey extraction
                $quad[self::KEY_DEPARTURE_CONNECTION] = $connection;
                $quad[self::KEY_ARRIVAL_CONNECTION] = $T[$connection->getTrip()][self::KEY_ARRIVAL_CONNECTION];

                $quad[self::KEY_TRANSFER_COUNT] = $numberOfTransfers;

                if (key_exists($departureStop, $S)) {
                    $numberOfPairs = count($S[$departureStop]);

                    $existingQuad = $S[$departureStop][$numberOfPairs - 1];
                    // If $existingQuad does not dominate $quad
                    // The new departure time is always less or equal than an already stored one

                    if ($quad[self::KEY_ARRIVAL_TIME] < $existingQuad[self::KEY_ARRIVAL_TIME]) {
                        // // Log::info("[{$connection->getId()}] Updating S: Reach $destination from $departureStop departing at {$quad[self::KEY_DEPARTURE_TIME]} arriving at {$quad[self::KEY_ARRIVAL_TIME]}");
                        if ($quad[self::KEY_DEPARTURE_TIME] == $existingQuad[self::KEY_DEPARTURE_TIME]) {
                            // Replace $existingQuad at the back
                            $S[$departureStop][$numberOfPairs - 1] = $quad;
                        } else {
                            // We're iterating over descending departure times, therefore the departure
                            // Insert at the back
                            $S[$departureStop][$numberOfPairs] = $quad;
                        }
                    }

                } else {
                    // Log::info("[{$connection->getId()}] Updating S: New: Reach $destination from $departureStop departing at {$quad[self::KEY_DEPARTURE_TIME]} arriving at {$quad[self::KEY_ARRIVAL_TIME]}");
                    $S[$departureStop] = [];
                    $S[$departureStop][] = $quad;
                }

                // ====================================================== //
                // END UPDATE S
                // ====================================================== //
            }
        }

        $results = [];
        if (!key_exists($origin, $S)) {
            return new ConnectionList(new Station($origin, $language), new Station($destination, $language), [], new Carbon(), Carbon::now()->addMinute(), md5($etag));
        }

        foreach ($S[$origin] as $k => $quad) {
            // $it will iterate over all the legs (journeys) in a connection
            $it = $quad;

            $journeys = [];

            // As long as $it doesn't contain the leg (journey) which arrives at our destination, keep searching.
            // Footpaths to walk to the destination aren't supported, therefore this is a valid check.
            while ($it[self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri() != $destination) {
                $journeys[] = new JourneyLeg($it[self::KEY_DEPARTURE_CONNECTION], $it[self::KEY_ARRIVAL_CONNECTION], $language);
                $it = $this->getFirstReachableConnection($S, $it);
            }

            // Store the last leg
            $journeys[] = new JourneyLeg($it[self::KEY_DEPARTURE_CONNECTION], $it[self::KEY_ARRIVAL_CONNECTION], $language);

            // Store the entire connection, in the meanwhile inversing the array to have earliest connections first
            $results[] = new Connection($journeys);
        }

        usort($results, function (Connection $a, Connection $b) {
            if ($a->getDepartureTime()->lessThan($b->getDepartureTime())) {
                return -1;
            } else if ($a->getDepartureTime()->equalTo($b->getDepartureTime())) {
                return 0;
            } else {
                return 1;
            }
        });


        // Store and return the list of connections
        return new ConnectionList(new Station($origin, $language),
            new Station($destination, $language),
            array_values($results),
            new Carbon(),
            Carbon::now()->addMinute(),
            md5($etag));
    }

    function getFirstReachableConnection($S, $arrivalQuad)
    {
        $it_options = $S[$arrivalQuad[self::KEY_ARRIVAL_CONNECTION]->getArrivalStopUri()];
        $i = count($it_options) - 1;
        // Find the next hop. This is the first reachable hop,
        // or even stricter defined: the hop which will get us to the destination at the same arrival time.
        // There will be a one second difference between the arrival times, as a result of the leg optimization
        while ($i >= 0 && $it_options[$i][self::KEY_ARRIVAL_TIME] != $arrivalQuad[self::KEY_ARRIVAL_TIME] - self::TransferEquivalentTravelTime) {
            $i--;
        }
        if ($i == -1) {
            $i = count($it_options) - 1;
            while ($i >= 0 && $it_options[$i][self::KEY_ARRIVAL_TIME] > $arrivalQuad[self::KEY_ARRIVAL_TIME] - self::TransferEquivalentTravelTime) {
                $i--;
            }
        }
        return $it_options[$i];
    }
}