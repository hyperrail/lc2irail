<?php

namespace App\Http\Models;

use Carbon\Carbon;

/**
 * Class TrainDeparture
 */
class TrainDeparture extends TrainStopBase implements \JsonSerializable
{
    private $departureTime;
    private $departureDelay;
    /**
     * @var bool
     */
    private $isDepartureCanceled;
    /**
     * @var bool
     */
    private $hasDeparted;

    public function __construct(
        string $uri,
        string $platform,
        bool $isPlatformNormal,
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
        $this->isDepartureCanceled = $isDepartureCanceled;
        $this->hasDeparted = $hasDeparted;
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
    public function isDepartureCanceled(): bool
    {
        return $this->isDepartureCanceled;
    }

    /**
     * @return bool
     */
    public function hasDeparted(): bool
    {
        return $this->hasDeparted;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        $vars['departureTime'] = $this->departureTime->toAtomString();

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