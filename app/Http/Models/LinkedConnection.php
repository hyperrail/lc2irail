<?php

namespace App\Http\Models;

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

    /**
     * @var string
     */
    private $departurePlatform;
    /**
     * @var string
     */
    private $arrivalPlatform;
    /**
     * @var bool
     */
    private $isNormalDeparturePlatform;
    /**
     * @var bool
     */
    private $isDepartureCanceled;
    /**
     * @var bool
     */
    private $isNormalArrivalPlatform;
    /**
     * @var bool
     */
    private $isArrivalCanceled;
    /**
     * @var bool
     */
    private $hasDeparted;
    /**
     * @var bool
     */
    private $hasArrived;

    /**
     * LinkedConnection constructor.
     * @param $entry array Array of JSON fields
     */
    public function __construct(
        $entry
    )
    {
        $arrivalDelay = key_exists('arrivalDelay', $entry) ? $entry['arrivalDelay'] : 0;
        $departureDelay = key_exists('departureDelay', $entry) ? $entry['departureDelay'] : 0;

        if (ends_with($departureDelay, "S")) {
            $departureDelay = substr($departureDelay, 0, strlen($departureDelay) - 1);
        }

        if (ends_with($arrivalDelay, "S")) {
            $arrivalDelay = substr($arrivalDelay, 0, strlen($arrivalDelay) - 1);
        }

        // TODO: support platforms
        // TODO; support changed platforms
        // TODO: support canceled trains

        $this->id = $entry['@id'];

        $this->departureStopURI = $entry['departureStop'];
        $this->departureTime = strtotime($entry['departureTime']);
        $this->departureDelay = $departureDelay;
        $this->departurePlatform = "?";

        $this->isNormalDeparturePlatform = true;
        $this->isDepartureCanceled = false;
        $this->hasDeparted = ($this->departureTime + $this->departureDelay < time());

        $this->arrivalStopURI = $entry['arrivalStop'];
        $this->arrivalTime = strtotime($entry['arrivalTime']);
        $this->arrivalDelay = $arrivalDelay;
        $this->arrivalPlatform = "?";
        $this->isNormalArrivalPlatform = true;
        $this->isArrivalCanceled = false;
        $this->hasArrived = ($this->arrivalTime + $this->arrivalDelay < time());

        $this->direction = $entry['direction'];
        $this->trip = $entry['gtfs:trip'];
        $this->route = basename($entry['gtfs:route']);
    }

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

    public function getDelayedDepartureTime(): int
    {
        return $this->departureTime + $this->getDepartureDelay();
    }

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

    public function getDepartureStopUri(): string
    {
        return $this->departureStopURI;
    }

    public function getDepartureTime(): int
    {
        return $this->departureTime;
    }

    /**
     * The direction headsign, which may or may not be a station, but should not be treated as a station unless you want crashing code somewhere in the future
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The route, also known as the vehicle
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    public function getTrip(): string
    {
        return $this->trip;
    }

    /**
     * @return bool
     */
    public function hasArrived(): bool
    {
        return $this->hasArrived;
    }

    /**
     * @return bool
     */
    public function hasDeparted(): bool
    {
        return $this->hasDeparted;
    }

    /**
     * @return bool
     */
    public function isArrivalCanceled(): bool
    {
        return $this->isArrivalCanceled;
    }

    /**
     * @return bool
     */
    public function isArrivalPlatformNormal(): bool
    {
        return $this->isNormalArrivalPlatform;
    }

    /**
     * @return bool
     */
    public function isDepartureCanceled(): bool
    {
        return $this->isDepartureCanceled;
    }

    /**
     * @return bool
     */
    public function isDeparturePlatformNormal(): bool
    {
        return $this->isNormalDeparturePlatform;
    }

}