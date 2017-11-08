<?php

namespace App\Http\Repositories;

use App\Http\Models\Liveboard;
use App\Http\Models\Station;
use App\Http\Models\Vehicle;
use Carbon\Carbon;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
interface VehicleRepositoryContract
{

    /**
     * Retrieve an array of LinkedConnection objects for a certain departure time
     *
     * @param string $name
     * @param string $date The date in yyyyMMdd format
     * @param string $language The language in ISO2 format
     * @return \App\Http\Models\Vehicle
     */
    public function getVehicle(String $name, string $date, string $language = ''): Vehicle;

}
