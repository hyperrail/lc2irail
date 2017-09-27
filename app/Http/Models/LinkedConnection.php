<?php

namespace App\Http\Models;

use irail\stations\Stations;

/**
 * Class LinkedConnection
 * A linkedconnection contains all information about one connection, returned by a linkedconnections API
 */
class LinkedConnection
{

    private $id;

    private $departureStopURI;
    private $departureTime;
    private $departureDelay;

    private $arrivalStopURI;
    private $arrivalTime;
    private $arrivalDelay;

    private $trip;
    private $route;

    public function __construct(
        string $id,
        string $departureStop,
        int $departureTime,
        int $departureDelay,
        string $arrivalStop,
        int $arrivalTime,
        int $arrivalDelay,
        string $trip,
        string $route
    ) {
        $this->id = $id;

        $this->departureStopURI = $departureStop;
        $this->departureTime = $departureTime;
        $this->departureDelay = $departureDelay;

        $this->arrivalStopURI = $arrivalStop;
        $this->arrivalTime = $arrivalTime;
        $this->arrivalDelay = $arrivalDelay;

        $this->trip = $trip;

        $routeParts = explode('/', $route);
        $this->route = array_pop($routeParts);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getDepartureStopUri(): string
    {
        return $this->departureStopURI;
    }

    /**
     * @return int
     */
    public function getDepartureTime(): int
    {
        return $this->departureTime;
    }

    /**
     * @return mixed
     */
    public function getDepartureDelay() : int
    {
        return $this->departureDelay;
    }

    public function getArrivalStopUri(): string
    {
        return $this->arrivalStopURI;
    }

    /**
     * @return int
     */
    public function getArrivalTime(): int
    {
        return $this->arrivalTime;
    }


    /**
     * @return mixed
     */
    public function getArrivalDelay() : int
    {
        return $this->arrivalDelay;
    }

    /**
     * @return int
     */
    public function getTrip(): string
    {
        return $this->trip;
    }

    /**
     * The route, also known as the vehicle
     * @return int
     */
    public function getRoute(): string
    {
        return $this->route;
    }

}