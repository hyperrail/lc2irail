<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Http\Models\Station;
use App\Http\Models\Liveboard;
use App\Http\Repositories\LiveboardsRepository;
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
        $cacheKey = "lc2irail|liveboard|$id|$window|$language";
        if (Cache::has($cacheKey)) {
            /**
             * @var $liveboard Liveboard
             */
            $liveboard = Cache::get($cacheKey);
            return response()->json($liveboard, 200) ->withHeaders([
                'Expires' => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
                'Cache-Control' => 'Public, max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
                'Last-Modified' =>  $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
                'ETag' => $liveboard->getEtag()
            ]);
        }

        /**
         * @var $repository LiveboardsRepositoryContract
         */
        $repository = new LiveboardsRepository();
        $liveboard = $repository->getDepartures($station, $request->getDateTime(), $language, $window);
        Cache::put($cacheKey, $liveboard, $liveboard->getExpiresAt());

        return response()->json($liveboard, 200) ->withHeaders([
            'Expires' => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' =>  $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag' => $liveboard->getEtag()
        ]);
    }

    public function getLiveboardByName(HyperrailRequest $request, string $name)
    {
        $id = Stations::getStations($name)->{'@graph'}[0]['@id'];
        return $this->getLiveboard($request, $id);
    }
}