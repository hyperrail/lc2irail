<?php

namespace App\Http\Models;

use irail\stations\Stations;

/**
 * Class TrainDeparture
 */
abstract class TrainStopBase
{

    protected $uri;

    protected $vehicle;

    protected $platform;

    protected $station;

    public function __construct(
        string $uri,
        string $platform,
        VehicleStub $vehicle = null,
        Station $station = null
    ) {
        $this->uri = $uri;
        $this->vehicle = $vehicle;
        $this->platform = $platform;
        $this->station = $station;
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
}