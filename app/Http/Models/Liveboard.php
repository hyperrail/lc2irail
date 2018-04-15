<?php

namespace App\Http\Models;

use Carbon\Carbon;


/**
 * Class Liveboard
 */
class Liveboard implements \JsonSerializable
{
    use ApiResponse;
    private $station;
    private $departures;

    private $arrivals;
    private $stops;


    public function __construct(
        Station $station,
        array $departures,
        array $stops,
        array $arrivals,
        Carbon $createdAt,
        Carbon $expiresAt,
        string $etag
    )
    {
        $this->createApiResponse($createdAt, $expiresAt, $etag);
        $this->station = $station;
        $this->departures = $departures;
        $this->stops = $stops;
        $this->arrivals = $arrivals;

    }

    /**
     * @return \App\Http\Models\TrainArrival[]
     */
    public function getArrivals(): array
    {
        return $this->arrivals;
    }

    /**
     * @return \App\Http\Models\TrainDeparture[]
     */
    public function getDepartures(): array
    {
        return $this->departures;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return \App\Http\Models\TrainStop[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['createdAt']);
        unset($vars['expiresAt']);
        unset($vars['etag']);

        return $vars;
    }

}