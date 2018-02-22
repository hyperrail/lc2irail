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


use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use irail\stations\Stations;
use Cache;

class ConnectionsController extends Controller
{

    public function getConnectionsByArrival(HyperrailRequest $request, $origin, $destination)
    {
        $language = $request->getLanguage();

        $origin = new Station($origin, $language);
        $destination = new Station($destination, $language);

        $cacheKey = "lc2irail|connections|" . $origin->getId() . "|" . $destination->getId() . "|arrival|" . $request->getDateTime()->getTimestamp() . $language;

        if (false && Cache::has($cacheKey)) {
            $result = Cache::get($cacheKey);

            return response()->json($result, 200)->withHeaders([
                'Expires' => $result->getExpiresAt()->format('D, d M Y H:i:s e'),
                'Cache-Control' => 'Public, max-age=' . $result->getExpiresAt()->diffInSeconds(new Carbon()),
                'Last-Modified' => $result->getCreatedAt()->format('D, d M Y H:i:s e'),
                'ETag' => $result->getEtag()
            ]);
        }

        /**
         * @var $repository ConnectionsRepository
         */
        $repository = new ConnectionsRepository();
        $result = $repository->getConnectionsByArrivalTime($origin->getUri(), $destination->getUri(), $request->getDateTime(), $language);

        //Cache::put($cacheKey, $result, $result->getExpiresAt());

        return response()->json($result, 200)->withHeaders([
            'Expires' => $result->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $result->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' => $result->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag' => $result->getEtag()
        ]);

    }

}