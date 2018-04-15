<?php

namespace App\Http\Models;

use Carbon\Carbon;

/**
 * Class TrainDeparture
 */
class TrainStop extends TrainStopBase implements \JsonSerializable
{
    private $departureTime;
    private $departureDelay;
    private $arrivalTime;
    private $arrivalDelay;
    /**
     * @var bool
     */
    private $isDepartureCanceled;
    /**
     * @var bool
     */
    private $hasDeparted;
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
        Carbon $departureTime,
        int $departureDelay,
        bool $isDepartureCanceled,
        bool $hasDeparted,
        VehicleStub $vehicle = null,
        Station $station = null
    )
    {
        parent::__construct($uri, $platform, $isPlatformNormal, $vehicle, $station);
        $this->departureTime = $departureTime;
        $this->departureDelay = $departureDelay;
        $this->arrivalTime = $arrivalTime;
        $this->arrivalDelay = $arrivalDelay;
        $this->isDepartureCanceled = $isDepartureCanceled;
        $this->hasDeparted = $hasDeparted;
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
     * @return int
     */
    public function getDepartureDelay(): int
    {
        return $this->departureDelay;
    }

    /**
     * @return Carbon
     */
    public function getDepartureTime(): Carbon
    {
        return $this->departureTime;
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
    public function isDepartureCanceled(): bool
    {
        return $this->isDepartureCanceled;
    }

    /**
     * @return bool
     */
    public function isHasArrived(): bool
    {
        return $this->hasArrived;
    }

    /**
     * @return bool
     */
    public function isHasDeparted(): bool
    {
        return $this->hasDeparted;
    }

    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        $vars['departureTime'] = $this->departureTime->toAtomString();
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