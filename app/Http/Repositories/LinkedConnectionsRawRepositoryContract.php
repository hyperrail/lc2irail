<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnectionPage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Array_;

/**
 * Class LinkedConnectionsRawRepositoryContract
 * A read-only repository for realtime train raw data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
interface LinkedConnectionsRawRepositoryContract
{

    /**
     * Retrieve raw LinkedConnection data
     *
     * @param Carbon $departureTime The time for which departures should be returned
     * @return Array
     */
    public function getRawLinkedConnections(Carbon $departureTime);

}