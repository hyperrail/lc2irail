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

    private $departureStopId;
    private $departureTime;
    private $departureDelay;

    private $arrivalStopId;
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

        $this->departureStopId = self::getHafasID($departureStop);
        $this->departureTime = $departureTime;

        $this->arrivalStopId = self::getHafasID($arrivalStop);
        $this->arrivalTime = $arrivalTime;

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

    /**
     * @return string
     */
    public function getDepartureStopId(): string
    {
        return $this->departureStopId;
    }

    public function getDepartureStop(): array
    {
        return (array)Stations::getStationFromID($this->departureStopId);
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

    /**
     * @return string
     */
    public function getArrivalStopId(): string
    {
        return $this->arrivalStopId;
    }

    public function getArrivalStop(): array
    {
        return (array)Stations::getStationFromID($this->arrivalStopId);
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
    public function getTrip(): int
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

    private static function getHafasID($id)
    {
        if (starts_with($id, 'http')) {
            $parts = explode('/', $id);
            $id = array_pop($parts);
        }

        return sprintf("%09d", $id);
    }

}