<?php

namespace App\Http\Models;

/**
 * Class Vehicle
 */
class VehicleStub implements \JsonSerializable
{

    protected $uri;
    protected $id;
    protected $direction;

    public function __construct(
        string $uri,
        string $id,
        string $direction
    )
    {
        $this->uri = $uri;
        $this->id = $id;
        $this->direction = $direction;
    }

    /**
     * @return Station
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }
}