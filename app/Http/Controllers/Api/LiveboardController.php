<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Models\LinkedConnection;
use App\Http\Repositories\LinkedConnectionsRepositoryContract;
use App\Http\Requests\HyperrailRequest;
use Carbon\Carbon;
use Composer\Cache;
use Illuminate\Support\Facades\Log;
use irail\stations\Stations;

class LiveboardController extends Controller
{

    public function getLiveboard(HyperrailRequest $request, int $id)
    {

        $repository = app(LinkedConnectionsRepositoryContract::class);
        $id = self::getHafasID($id);
        $departureStation = (array)Stations::getStationFromID($id);

        // The size of the window (in seconds), for which data should be retrieved
        $window = $request->get('window', 3600);

        $departures = $repository->getLinkedConnectionsInWindow($request->getDateTime(), $window);

        Log::info("Got " . sizeof($departures) . " departures");

        $relevantConnections = [];
        foreach ($departures as $connection) {
            if ($connection->getDepartureStopId() == $id) {
                $relevantConnections[] = $this->formatDeparture($connection);
            }
        }
        Log::info("Got " . sizeof($relevantConnections) . " relevant departures");

        $output = [];
        $output['station'] = $departureStation['name'];
        $output['stationinfo'] = $departureStation;
        unset($output['stationinfo']['alternative']);
        $output['departures'] = $relevantConnections;


        return response()->json((array)$output, 200);
    }

    private function formatDeparture(LinkedConnection $departure): array
    {
        $arrivalstop = $departure->getArrivalStop();

        $connection = [];
        $connection['id'] = $departure->getId();
        $connection['station'] = $arrivalstop['name'];

        $connection['stationinfo'] = $arrivalstop;
        unset($connection['stationinfo']['alternative']);

        $connection['time'] = $departure->getDepartureTime();
        $connection['canceled'] = 0;
        $connection['left'] = 0;
        $connection['departureDelay'] = $departure->getDepartureDelay();
        $connection['arrivalDelay'] = $departure->getArrivalDelay();
        $connection['vehicle'] = $departure->getRoute();

        return $connection;
    }

    public function getLiveboardByName(HyperrailRequest $request, string $name)
    {
        if (Cache::has('station:' . $name)) {
            $id = Cache::get('station:' . $name);
        } else {
            $id = Stations::getStations($name)->{'@graph'}[0]['@id'];
            $id = str_replace('http://irail.be/stations/NMBS/', '', $id);
            Cache::put('station:' . $name);
        }

        return $this->getLiveboard($request, $id);
    }
}