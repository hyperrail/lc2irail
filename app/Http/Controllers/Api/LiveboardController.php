<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Http\Models\Station;
use App\Http\Repositories\LiveboardsRepositoryContract;
use App\Http\Requests\HyperrailRequest;
use Carbon\Carbon;


use Illuminate\Support\Facades\Log;
use irail\stations\Stations;
use Cache;

class LiveboardController extends Controller
{

    public function getLiveboard(HyperrailRequest $request, string $id)
    {
        // The size of the window (in seconds), for which data should be retrieved
        $window = $request->get('window', 3600);
        $language = $request->getLanguage();

        $station = new Station($id, $language);

        /**
         * @var $repository LiveboardsRepositoryContract
         */
        $repository = app(LiveboardsRepositoryContract::class);
        $liveboard = $repository->getDepartures($station, $request->getDateTime(), $language, $window);

        return response()->json($liveboard, 200);
    }

    public function getLiveboardByName(HyperrailRequest $request, string $name)
    {
        $id = Stations::getStations($name)->{'@graph'}[0]['@id'];
        return $this->getLiveboard($request, $id);
    }
}