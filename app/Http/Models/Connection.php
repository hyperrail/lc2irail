<?php

namespace App\Http\Models;

use Carbon\Carbon;

/**
 * Class Connection
 * A connection is a single path from A to B with possible transfers in C, D, E, ...
 */
class Connection implements \JsonSerializable
{
    /**
     * @var array A list of trains journeys
     */
    private $legs;
    private $departureTime;
    private $arrivalTime;

    public function __construct(array $journeys)
    {
        $this->legs = $journeys;
        $this->departureTime = $journeys[0]->getDepartureTime();
        $this->arrivalTime = $journeys[count($journeys) - 1]->getArrivalTime();
    }

    /**
     * @return mixed
     */
    public function getArrivalTime(): Carbon
    {
        return $this->arrivalTime;
    }

    /**
     * @return mixed
     */
    public function getDepartureTime(): Carbon
    {
        return $this->departureTime;
    }

    /**
     * @return array
     */
    public function getLegs(): array
    {
        return $this->legs;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        $vars['departureTime'] = $this->departureTime->toAtomString();
        $vars['arrivalTime'] = $this->arrivalTime->toAtomString();
        return $vars;
    }
}