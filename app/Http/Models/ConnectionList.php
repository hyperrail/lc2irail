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
    use Browseable;

    /**
     * @var Station
     */
    private $departureStation;


    /**
     * @var Station
     */
    private $arrivalStation;


    /**
     * @var array[Connection]
     */
    private $connections;

    public function __construct(Station $origin,
                                Station $destination,
                                array $connections,
                                Carbon $createdAt,
                                Carbon $expiresAt,
                                string $etag,
                                string $previous,
                                string $current,
                                string $next
    )
    {
        $this->createApiResponse($createdAt, $expiresAt, $etag);
        $this->createBrowseable($previous, $current, $next);
        $this->departureStation = $origin;
        $this->arrivalStation = $destination;
        $this->connections = $connections;
    }

    /**
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @return Station
     */
    public function getDepartureStation(): Station
    {
        return $this->departureStation;
    }

    /**
     * @return Station
     */
    public function getArrivalStation(): Station
    {
        return $this->arrivalStation;
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