<?php

namespace App\Http\Models;

use Carbon\Carbon;

/**
 * Class ConnectionList
 * A connectionList is a list of connections from A to B, where no connection is completely dominated by another connection in this list
 */
class ConnectionList implements \JsonSerializable
{
    use ApiResponse;

    /**
     * @var Station
     */
    private $origin;


    /**
     * @var Station
     */
    private $destination;


    /**
     * @var array[Connection]
     */
    private $connections;

    public function __construct(Station $origin,
                                Station $destination,
                                array $connections,
                                Carbon $createdAt,
                                Carbon $expiresAt,
                                string $etag
    )
    {
        $this->createApiResponse($createdAt, $expiresAt, $etag);
        $this->origin = $origin;
        $this->destination = $destination;
        $this->connections = $connections;
    }

    /**
     * @return Station
     */
    public function getOrigin(): Station
    {
        return $this->origin;
    }

    /**
     * @return Station
     */
    public function getDestination(): Station
    {
        return $this->destination;
    }

    /**
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
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