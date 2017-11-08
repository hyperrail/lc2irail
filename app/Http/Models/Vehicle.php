<?php

namespace App\Http\Models;

use Carbon\Carbon;

/**
 * Class Vehicle
 */
class Vehicle extends VehicleStub implements \JsonSerializable
{
    use ApiResponse;

    private $stops;


    public function __construct(
        string $uri,
        string $id,
        string $direction,
        array $stops,
        Carbon $createdAt,
        Carbon $expiresAt,
        string $etag
    ) {
        parent::__construct($uri, $id, $direction);
        $this->createApiResponse($createdAt, $expiresAt, $etag);

        $this->stops = $stops;

    }

    /**
     * @return \App\Http\Models\TrainStop[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }


    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['createdAt']);
        unset($vars['expiresAt']);
        unset($vars['etag']);

        return $vars;
    }

}