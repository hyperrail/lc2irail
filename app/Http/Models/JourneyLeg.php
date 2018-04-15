<?php

namespace App\Http\Models;

use Carbon\Carbon;

class JourneyLeg implements \JsonSerializable
{

    /**
     * @var string The URI defining this departure stop
     */
    private $departureUri;

    /**
     * @var string The URI defining this arrival stop
     */
    private $arrivalUri;

    /**
     * @var string The Trip
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
    private $departureTime;
    /**
     * @var \Carbon\Carbon
     */
    private $arrivalTime;
    /**
     * @var int
     */
    private $departureDelay;
    /**
     * @var int
     */
    private $arrivalDelay;
    /**
     * @var string
     */
    private $departurePlatform;
    /**
     * @var string
     */
    private $arrivalPlatform;

    /**
     * @var \App\Http\Models\Station
     */
    private $departureStation;

    /**
     * @var \App\Http\Models\Station
     */
    private $arrivalStation;

    /**
     * @var bool
     */
    private $hasArrived;

    /**
     * @var bool
     */
    private $isArrivalCanceled;

    /**
     * @var bool
     */
    private $isArrivalPlatformNormal;

    /**
     * @var bool
     */
    private $hasLeft;

    /**
     * @var bool
     */
    private $isDepartureCanceled;

    /**
     * @var bool
     */
    private $isDeparturePlatformNormal;

    public function __construct(LinkedConnection $departureConnection, LinkedConnection $arrivalConnection, $language)
    {
        $this->departureUri = $departureConnection->getId();
        $this->departureStation = new Station($departureConnection->getDepartureStopUri(), $language);
        $this->departureTime = Carbon::createFromTimestamp($departureConnection->getDepartureTime());
        $this->departureDelay = $departureConnection->getDepartureDelay();
        $this->departurePlatform = $departureConnection->getDeparturePlatform();
        $this->isDeparturePlatformNormal = $departureConnection->isDeparturePlatformNormal();
        $this->isDepartureCanceled = $departureConnection->isDepartureCanceled();
        $this->hasLeft = $departureConnection->hasDeparted();

        $this->arrivalUri = $arrivalConnection->getId();
        $this->arrivalStation = new Station($arrivalConnection->getArrivalStopUri(), $language);
        $this->arrivalTime = Carbon::createFromTimestamp($arrivalConnection->getArrivalTime());
        $this->arrivalDelay = $arrivalConnection->getArrivalDelay();
        $this->arrivalPlatform = $arrivalConnection->getArrivalPlatform();
        $this->isArrivalPlatformNormal = $arrivalConnection->isArrivalPlatformNormal();
        $this->isArrivalCanceled = $arrivalConnection->isArrivalCanceled();
        $this->hasArrived = $arrivalConnection->hasArrived();

        $this->trip = $departureConnection->getTrip();
        $this->route = $departureConnection->getRoute();
        $this->direction = $departureConnection->getDirection();
    }

    /**
     * @return int
     */
    public function getArrivalDelay(): int
    {
        return $this->arrivalDelay;
    }

    /**
     * @return string
     */
    public function getArrivalPlatform(): string
    {
        return $this->arrivalPlatform;
    }

    /**
     * @return Station
     */
    public function getArrivalStation(): Station
    {
        return $this->arrivalStation;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getArrivalTime(): \Carbon\Carbon
    {
        return $this->arrivalTime;
    }

    /**
     * @return string
     */
    public function getArrivalUri(): string
    {
        return $this->arrivalUri;
    }

    /**
     * @return int
     */
    public function getDepartureDelay(): int
    {
        return $this->departureDelay;
    }

    /**
     * @return string
     */
    public function getDeparturePlatform(): string
    {
        return $this->departurePlatform;
    }

    /**
     * @return Station
     */
    public function getDepartureStation(): Station
    {
        return $this->departureStation;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getDepartureTime(): \Carbon\Carbon
    {
        return $this->departureTime;
    }

    /**
     * @return string
     */
    public function getDepartureUri(): string
    {
        return $this->departureUri;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getTrip(): string
    {
        return $this->trip;
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
        $vars['departureTime'] = $this->departureTime->toAtomString();
        $vars['arrivalTime'] = $this->arrivalTime->toAtomString();
        ksort($vars);
        return $vars;
    }
}