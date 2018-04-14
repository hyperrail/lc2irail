<?php

namespace App\Http\Models;

use Carbon\Carbon;


/**
 * Class LinkedConnection
 * A linkedconnection contains all information about one connection, returned by a linkedconnections API
 */
class LinkedConnection
{

    public $id;

    public $departureStopURI;
    public $departureTime;
    public $departureDelay;

    public $arrivalStopURI;
    public $arrivalTime;
    public $arrivalDelay;

    public $direction;

    public $trip;
    public $route;

    public function __construct(
        string $id,
        string $departureStop,
        int $departureTime,
        int $departureDelay,
        string $arrivalStop,
        int $arrivalTime,
        int $arrivalDelay,
        string $direction,
        string $trip,
        string $route
    )
    {
        $this->id = $id;

        $this->departureStopURI = $departureStop;
        $this->departureTime = $departureTime;
        $this->departureDelay = $departureDelay;

        $this->arrivalStopURI = $arrivalStop;
        $this->arrivalTime = $arrivalTime;
        $this->arrivalDelay = $arrivalDelay;

        $this->direction = $direction;
        $this->trip = $trip;
        $this->route = basename($route);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDepartureStopUri(): string
    {
        return $this->departureStopURI;
    }

    public function getDepartureTime(): int
    {
        return $this->departureTime;
    }

    public function getDelayedDepartureTime(): int
    {
        return $this->departureTime + $this->getDepartureDelay();
    }

    public function getDepartureDelay(): int
    {
        return $this->departureDelay;
    }

    public function getArrivalStopUri(): string
    {
        return $this->arrivalStopURI;
    }

    public function getArrivalTime(): int
    {
        return $this->arrivalTime;
    }

    public function getDelayedArrivalTime(): int
    {
        return $this->arrivalTime + $this->arrivalDelay;
    }

    public function getArrivalDelay(): int
    {
        return $this->arrivalDelay;
    }

    public function getTrip(): string
    {
        return $this->trip;
    }

    /**
     * The route, also known as the vehicle
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * The direction headsign, which may or may not be a station, but should not be treated as a station unless you want crashing code somewhere in the future
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

}