<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Http\Models\Station;
use App\Http\Models\Liveboard;
use App\Http\Repositories\ConnectionsRepository;
use App\Http\Repositories\LiveboardsRepository;
use App\Http\Repositories\LiveboardsRepositoryContract;
use App\Http\Requests\HyperrailRequest;
use Carbon\Carbon;


use Illuminate\Support\Facades\Log;
use irail\stations\Stations;
use Cache;

class ConnectionsController extends Controller
{

    public function getConnectionsByArrival(HyperrailRequest $request, $origin, $destination)
    {
        // The size of the window (in seconds), for which data should be retrieved
        $window = $request->get('window', 3600);
        $language = $request->getLanguage();

        $origin = new Station($origin, $language);
        $destination = new Station($destination, $language);


        $cacheKey = "lc2irail|connections|" . $origin->getId() . "|" . $destination->getId() ."|arrival|$language";
        /*if (Cache::has($cacheKey)) {

            $liveboard = Cache::get($cacheKey);
            return response()->json($liveboard, 200)->withHeaders([
                'Expires' => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
                'Cache-Control' => 'Public, max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
                'Last-Modified' => $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
                'ETag' => $liveboard->getEtag()
            ]);
        }*/

        /**
         * @var $repository ConnectionsRepository
         */
        $repository = new ConnectionsRepository();
        $result = $repository->getConnectionsByArrivalTime($origin->getUri(), $destination->getUri(), $request->getDateTime());
        /*
           Cache::put($cacheKey, $result, $result->getExpiresAt());

           return response()->json($liveboard, 200)->withHeaders([
               'Expires' => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
               'Cache-Control' => 'max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
               'Last-Modified' => $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
               'ETag' => $liveboard->getEtag()
           ]);*/
    }

}