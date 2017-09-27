<?php

namespace App\Http\Models;


/**
 * Class Liveboard
 */
class DeparturesLiveboard implements \JsonSerializable
{

    private $station;
    private $departures;

    public function __construct(Station $station, array $departures)
    {
        $this->station = $station;
        $this->departures = $departures;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return \App\Http\Models\TrainDeparture[]
     */
    public function getDepartures(): array
    {
        return $this->departures;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }

}