<?php

namespace App\Http\Models;

use irail\stations\Stations;

/**
 * Class Vehicle
 */
class Vehicle implements \JsonSerializable
{

    private $uri;
    private $id;
    private $name;
    private $direction;

    public function __construct(
        string $uri,
        string $id,
        string $name,
        Station $direction
    ) {
        $this->uri = $uri;
        $this->id = $id;
        $this->name = $name;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Station
     */
    public function getDirection(): Station
    {
        return $this->direction;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }
}