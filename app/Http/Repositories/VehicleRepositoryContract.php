<?php

namespace App\Http\Repositories;

use App\Http\Models\Liveboard;
use App\Http\Models\Station;
use App\Http\Models\Vehicle;
use Carbon\Carbon;
use Vehicle;


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
     * @param String         $name
     * @param \Carbon\Carbon $date
     * @param string         $language
     * @return \App\Http\Models\Vehicle
     */
    public function getVehicle(String $name, Carbon $date, string $language = ''): Vehicle;

}
