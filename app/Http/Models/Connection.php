<?php

namespace App\Http\Models;

/**
 * Class Connection
 * A connection is a single path from A to B with possible transfers in C, D, E, ...
 */
class Connection implements \JsonSerializable
{
    /**
     * @var array A list of trains journeys
     */
    private $journeys;

    public function __construct(array $journeys)
    {
        $this->journeys = $journeys;
    }

    /**
     * @return array
     */
    public function getJourneys(): array
    {
        return $this->journeys;
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
        return get_object_vars($this);
    }
}