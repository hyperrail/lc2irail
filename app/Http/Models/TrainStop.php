<?php

namespace App\Http\Models;

use Carbon\Carbon;
use irail\stations\Stations;

/**
 * Class TrainDeparture
 */
class TrainStop extends TrainStopBase implements \JsonSerializable
{
    private $departureTime;
    private $departureDelay;
    private $arrivalTime;
    private $arrivalDelay;
    private $departureTimestamp;
    private $arrivalTimestamp;

    public function __construct(
        string $uri,
        int $platform,
        Carbon $arrivalTime,
        int $arrivalDelay,
        Carbon $departureTime,
        int $departureDelay,
        VehicleStub $vehicle = null
    ) {
        parent::__construct($uri, $platform,$vehicle);
        $this->departureTime = $departureTime;
        $this->departureTimestamp = $this->departureTime->toAtomString();
        $this->departureDelay = $departureDelay;
        $this->arrivalTime = $arrivalTime;
        $this->arrivalTimestamp = $this->arrivalTime->toAtomString();
        $this->arrivalDelay = $arrivalDelay;
    }

    /**
     * @return Carbon
     */
    public function getDepartureTime(): Carbon
    {
        return $this->departureTime;
    }

    /**
     * @return int
     */
    public function getDepartureDelay(): int
    {
        return $this->departureDelay;
    }

    /**
     * @return Carbon
     */
    public function getArrivalTime(): Carbon
    {
        return $this->arrivalTime;
    }

    /**
     * @return int
     */
    public function getArrivalDelay(): int
    {
        return $this->arrivalDelay;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset ($vars['departureTime']);
        unset ($vars['arrivalTime']);
        if ($this->getVehicle() == null) {
            unset($vars['vehicle']);
        }
        return $vars;
    }

}