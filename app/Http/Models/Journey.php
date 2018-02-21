<?php

namespace App\Http\Models;

use Carbon\Carbon;

class Journey implements \JsonSerializable
{

    /**
     * @var string
     */
    private $departureConnection;

    /**
     * @var string
     */
    private $arrivalConnection;

    /**
     * @var string
     */
    private $trip;

    /**
     * @var string
     */
    private $route;

    /**
     * @var string
     */
    private $direction;

    /**
     * @var \Carbon\Carbon
     */
    private $scheduledDepartureTime;
    private $scheduledDepartureTimeStamp;
    /**
     * @var \Carbon\Carbon
     */
    private $scheduledArrivalTime;
    private $scheduledArrivalTimeStamp;
    /**
     * @var int
     */
    private $departureDelay;
    /**
     * @var int
     */
    private $arrivalDelay;
    /**
     * @var \App\Http\Models\Station
     */
    private $departureStation;
    /**
     * @var \App\Http\Models\Station
     */
    private $arrivalStation;

    public function __construct(\App\Http\Models\LinkedConnection $departureConnection, \App\Http\Models\LinkedConnection $arrivalConnection)
    {
        $this->departureConnection = $departureConnection->getId();
        $this->departureStation = new Station($departureConnection->getDepartureStopUri());
        $this->scheduledDepartureTime = Carbon::createFromTimestamp($departureConnection->getDepartureTime());
        $this->departureDelay = $departureConnection->getDepartureDelay();
        $this->scheduledDepartureTimeStamp = $this->scheduledDepartureTime->toAtomString();

        $this->arrivalConnection = $arrivalConnection->getId();
        $this->arrivalStation = new Station($arrivalConnection->getArrivalStopUri());
        $this->scheduledArrivalTime = Carbon::createFromTimestamp($arrivalConnection->getArrivalTime());
        $this->arrivalDelay = $arrivalConnection->getArrivalDelay();
        $this->scheduledArrivalTimeStamp = $this->scheduledArrivalTime->toAtomString();

        $this->trip = $departureConnection->getTrip();
        $this->route = $departureConnection->getRoute();
        $this->direction = $departureConnection->getDirection();
    }

    /**
     * @return string
     */
    public function getDepartureConnection(): string
    {
        return $this->departureConnection;
    }

    /**
     * @return string
     */
    public function getArrivalConnection(): string
    {
        return $this->arrivalConnection;
    }

    /**
     * @return string
     */
    public function getTrip(): string
    {
        return $this->trip;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getScheduledDepartureTime(): \Carbon\Carbon
    {
        return $this->scheduledDepartureTime;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getScheduledArrivalTime(): \Carbon\Carbon
    {
        return $this->scheduledArrivalTime;
    }

    /**
     * @return int
     */
    public function getDepartureDelay(): int
    {
        return $this->departureDelay;
    }

    /**
     * @return int
     */
    public function getArrivalDelay(): int
    {
        return $this->arrivalDelay;
    }

    /**
     * @return Station
     */
    public function getDepartureStation(): Station
    {
        return $this->departureStation;
    }

    /**
     * @return Station
     */
    public function getArrivalStation(): Station
    {
        return $this->arrivalStation;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['scheduledArrivalTime']);
        unset($vars['scheduledDepartureTime']);
        return $vars;
    }
}