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

    public function __construct(
        string $uri,
        Vehicle $vehicle,
        string $platform
    ) {
        $this->uri = $uri;
        $this->vehicle = $vehicle;
        $this->platform = $platform;
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
    public function getVehicle(): Vehicle
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
}