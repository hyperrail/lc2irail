<?php

namespace App\Http\Repositories;

use App\Http\Models\DeparturesLiveboard;
use App\Http\Models\Station;
use Carbon\Carbon;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
interface LiveboardsRepositoryContract
{

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param \App\Http\Models\Station $station
     * @param Carbon                   $departureTime The time for which departures should be returned
     * @param string                   $language
     * @param int                      $window
     * @return \App\Http\Models\DeparturesLiveboard
     */
    public function getDepartures(Station $station, Carbon $departureTime, string $language = '', int $window = 3600): DeparturesLiveboard;

}
