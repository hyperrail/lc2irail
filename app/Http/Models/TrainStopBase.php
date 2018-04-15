<?php

namespace App\Http\Models;

/**
 * Class TrainDeparture
 */
abstract class TrainStopBase
{

    protected $uri;

    protected $vehicle;

    protected $platform;

    protected $station;

    /**
     * @var bool
     */
    private $isPlatformNormal;

    public function __construct(
        string $uri,
        string $platform,
        bool $isPlatformNormal,
        VehicleStub $vehicle = null,
        Station $station = null
    )
    {
        $this->uri = $uri;
        $this->vehicle = $vehicle;
        $this->platform = $platform;
        $this->station = $station;
        $this->isPlatformNormal = $isPlatformNormal;
    }

    /**
     * @return int
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @return Station
     */
    public function getStation(): ?Station
    {
        return $this->station;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return Vehicle
     */
    public function getVehicle(): ?VehicleStub
    {
        return $this->vehicle;
    }

    /**
     * @return bool
     */
    public function isPlatformNormal(): bool
    {
        return $this->isPlatformNormal;
    }
}