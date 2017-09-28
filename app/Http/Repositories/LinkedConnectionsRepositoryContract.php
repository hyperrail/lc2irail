<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnectionPage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
interface LinkedConnectionsRepositoryContract
{

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime The time for which departures should be returned
     * @return \App\Http\Models\LinkedConnectionPage
     */
    public function getLinkedConnections(Carbon $departureTime): LinkedConnectionPage;

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param Carbon $departureTime The time for which departures should be returned
     * @param int    $window        The window, in seconds, for which departures should be retrieved
     * @return \App\Http\Models\LinkedConnectionPage
     */
    public function getLinkedConnectionsInWindow(Carbon $departureTime, int $window = 600): LinkedConnectionPage;

}
