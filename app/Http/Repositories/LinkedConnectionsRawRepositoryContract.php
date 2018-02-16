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
     * @param Carbon $pointer The pointer to which data should be retrieved.
     *                        This can either be a Carbon datetime, or any object returned as next/previous pointer by a previous response.
     * @return Array
     */
    public function getRawLinkedConnections($pointer);

}