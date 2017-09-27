<?php

namespace App\Http\Models;



/**
 * Class Liveboard
 */
class ArrivalsLiveboard implements \JsonSerializable
{

    private $station;
    private $arrivals;

    public function __construct(Station $station, array $arrivals)
    {
        $this->station = $station;
        $this->arrivals = $arrivals;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }
}