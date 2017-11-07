<?php

namespace App\Http\Models;

use irail\stations\Stations;

/**
 * Class Vehicle
 */
class VehicleStub implements \JsonSerializable
{

    private $uri;
    private $id;
    private $direction;

    public function __construct(
        string $uri,
        string $id,
        string $direction
    ) {
        $this->uri = $uri;
        $this->id = $id;
        $this->direction = $direction;
    }
    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }
}