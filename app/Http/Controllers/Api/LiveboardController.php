<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Models\Liveboard;
use App\Http\Models\Station;
use App\Http\Repositories\LiveboardsRepository;
use App\Http\Repositories\LiveboardsRepositoryContract;
use App\Http\Requests\HyperrailRequest;
use Cache;
use Carbon\Carbon;
use irail\stations\Stations;


class LiveboardController extends Controller
{

    public function getLiveboardNow(HyperrailRequest $request, string $id)
    {
        return redirect()->route('liveboard.byId', ['id' => $id, 'timestamp' => Carbon::now()->toAtomString()]);
    }

    public function getLiveboardByUICNow(HyperrailRequest $request, string $id)
    {
        // UIC Id to Hafas ID
        $id = "00" . $id;

        return redirect()->route('liveboard.byId', ['id' => $id, 'timestamp' => Carbon::now()->toAtomString()]);
    }

    public function getLiveboardByNameNow(HyperrailRequest $request, string $station)
    {
        $matches = Stations::getStations(urldecode($station))->{'@graph'};
        if (count($matches) == 0) {
            abort(404);
        }
        $id = basename($matches[0]->{'@id'});
        return redirect()->route('liveboard.byId', ['id' => $id, 'timestamp' => Carbon::now()->toAtomString()]);
    }

    public function getLiveboard(HyperrailRequest $request, string $id, $timestamp)
    {
        // UIC Id to Hafas ID
        if (strlen($id) == 7) {
            $id = "00" . $id;
        }

        // The size of the window (in seconds), for which data should be retrieved
        $window = $request->get('window', 3600);
        $language = $request->getLanguage();

        if (is_numeric($timestamp)) {
            $requestTime = Carbon::createFromTimestamp($timestamp);
        } else {
            $requestTime = Carbon::parse($timestamp);
        }

        $station = new Station($id, $language);
        $cacheKey = "lc2irail|liveboard|$id|after|" . $requestTime->getTimestamp() . "|$window|$language";
        if (Cache::has($cacheKey)) {
            /**
             * @var $liveboard Liveboard
             */
            $liveboard = Cache::get($cacheKey);
            return response()->json($liveboard, 200)->withHeaders([
                'Expires'       => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
                'Cache-Control' => 'Public, max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
                'Last-Modified' => $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
                'ETag'          => $liveboard->getEtag()
            ]);
        }

        /**
         * @var $repository LiveboardsRepositoryContract
         */
        $repository = new LiveboardsRepository();
        $liveboard = $repository->getLiveboard($station, $requestTime, $language, $window);
        Cache::put($cacheKey, $liveboard, $liveboard->getExpiresAt());

        return response()->json($liveboard, 200)->withHeaders([
            'Expires'       => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' => $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag'          => $liveboard->getEtag()
        ]);
    }
    public function getLiveboardByUIC(HyperrailRequest $request, string $id, $timestamp)
    {
        // UIC Id to Hafas ID
        $id = "00" . $id;

        return redirect()->route('liveboard.byId', ['id' => $id, 'timestamp' => $timestamp]);
    }

    public function getLiveboardByName(HyperrailRequest $request, string $station, $timestamp)
    {
        $matches = Stations::getStations(urldecode($station))->{'@graph'};
        if (count($matches) == 0) {
            abort(404);
        }
        $id = basename($matches[0]->{'@id'});
        return redirect()->route('liveboard.byId', ['id' => $id, 'timestamp' => $timestamp]);
    }


    public function getLiveboardBefore(HyperrailRequest $request, string $id, $timestamp)
    {
        // The size of the window (in seconds), for which data should be retrieved
        $window = $request->get('window', 3600);
        $language = $request->getLanguage();

        if (is_numeric($timestamp)) {
            $requestTime = Carbon::createFromTimestamp($timestamp);
        } else {
            $requestTime = Carbon::parse($timestamp);
        }

        $station = new Station($id, $language);
        $cacheKey = "lc2irail|liveboard|$id|before|" . $requestTime->getTimestamp() . "|$window|$language";
        if (Cache::has($cacheKey)) {
            /**
             * @var $liveboard Liveboard
             */
            $liveboard = Cache::get($cacheKey);
            return response()->json($liveboard, 200)->withHeaders([
                'Expires'       => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
                'Cache-Control' => 'Public, max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
                'Last-Modified' => $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
                'ETag'          => $liveboard->getEtag()
            ]);
        }

        /**
         * @var $repository LiveboardsRepositoryContract
         */
        $repository = new LiveboardsRepository();
        $liveboard = $repository->getLiveboardBefore($station, $requestTime, $language, $window);
        Cache::put($cacheKey, $liveboard, $liveboard->getExpiresAt());

        return response()->json($liveboard, 200)->withHeaders([
            'Expires'       => $liveboard->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $liveboard->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' => $liveboard->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag'          => $liveboard->getEtag()
        ]);
    }

    public function getLiveboardByNameBefore(HyperrailRequest $request, string $station, $timestamp)
    {
        $matches = Stations::getStations(urldecode($station))->{'@graph'};
        if (count($matches) == 0) {
            abort(404);
        }
        $id = basename($matches[0]->{'@id'});
        return redirect()->route('liveboard.byIdBefore', ['id' => $id, 'timestamp' => $timestamp]);
    }

    public function getLiveboardByUICBefore(HyperrailRequest $request, string $id, $timestamp)
    {
        // UIC Id to Hafas ID
        $id = "00" . $id;

        return redirect()->route('liveboard.byIdBefore', ['id' => $id, 'timestamp' => $timestamp]);
    }


}