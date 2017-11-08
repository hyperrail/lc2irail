<?php

namespace App\Http\Models;

use Carbon\Carbon;
use irail\stations\Stations;

/**
 * Class TrainDeparture
 */
class TrainArrival extends TrainStopBase implements \JsonSerializable
{

    private $arrivalTime;
    private $arrivalDelay;
    private $arrivalTimestamp;

    public function __construct(
        string $uri,
        VehicleStub $vehicle,
        int $platform,
        Carbon $arrivalTime,
        int $arrivalDelay
    ) {
        parent::__construct($uri,$platform,$vehicle);
        $this->arrivalTime = $arrivalTime;
        $this->arrivalTimestamp = $this->arrivalTime->toAtomString();
        $this->arrivalDelay = $arrivalDelay;
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
        unset ($vars['arrivalTime']);
        if ($this->getVehicle() == null) {
            unset($vars['vehicle']);
        }
        return $vars;
    }
}