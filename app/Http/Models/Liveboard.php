<?php

namespace App\Http\Models;

use Carbon\Carbon;


/**
 * Class Liveboard
 */
class Liveboard implements \JsonSerializable
{

    private $station;
    private $departures;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var Carbon
     */
    private $expiresAt;
    private $arrivals;
    private $stops;
    private $etag;


    public function __construct(
        Station $station,
        array $departures,
        array $stops,
        array $arrivals,
        Carbon $createdAt,
        Carbon $expiresAt,
        string $etag
    ) {
        $this->station = $station;
        $this->departures = $departures;
        $this->stops = $stops;
        $this->arrivals = $arrivals;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->etag = $etag;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return \App\Http\Models\TrainDeparture[]
     */
    public function getDepartures(): array
    {
        return $this->departures;
    }

    /**
     * @return \App\Http\Models\TrainStop[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }

    /**
     * @return \App\Http\Models\TrainArrival[]
     */
    public function getArrivals(): array
    {
        return $this->arrivals;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['createdAt']);
        unset($vars['expiresAt']);
        unset($vars['etag']);

        return $vars;
    }

    /**
     * @return string
     */
    public function getEtag(): string
    {
        return $this->etag;
    }
}