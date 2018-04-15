<?php

namespace App\Http\Models;

use Carbon\Carbon;

/**
 * Class TrainDeparture
 */
class TrainArrival extends TrainStopBase implements \JsonSerializable
{

    private $arrivalTime;
    private $arrivalDelay;
    /**
     * @var bool
     */
    private $isArrivalCanceled;
    /**
     * @var bool
     */
    private $hasArrived;

    public function __construct(
        string $uri,
        string $platform,
        bool $isPlatformNormal,
        Carbon $arrivalTime,
        int $arrivalDelay,
        bool $isArrivalCanceled,
        bool $hasArrived,
        VehicleStub $vehicle = null,
        Station $station = null
    )
    {
        parent::__construct($uri, $platform, $isPlatformNormal, $vehicle, $station);
        $this->arrivalTime = $arrivalTime;
        $this->arrivalDelay = $arrivalDelay;
        $this->isArrivalCanceled = $isArrivalCanceled;
        $this->hasArrived = $hasArrived;
    }

    /**
     * @return int
     */
    public function getArrivalDelay(): int
    {
        return $this->arrivalDelay;
    }

    /**
     * @return Carbon
     */
    public function getArrivalTime(): Carbon
    {
        return $this->arrivalTime;
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
    public function hasArrived(): bool
    {
        return $this->hasArrived;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        $vars['arrivalTime'] = $this->arrivalTime->toAtomString();
        if ($this->getVehicle() == null) {
            unset($vars['vehicle']);
        }
        if ($this->getStation() == null) {
            unset($vars['station']);
        }
        ksort($vars);
        return $vars;
    }
}