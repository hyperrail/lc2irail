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
    public function getConnections(HyperrailRequest $request, $origin, $destination, $departureTimestamp, $arrivalTimestamp)
    {
        if (is_numeric($departureTimestamp)) {
            $departureTime = Carbon::createFromTimestamp($departureTimestamp);
        } else {
            $departureTime = Carbon::parse($departureTimestamp);
        }

        if (is_numeric($arrivalTimestamp)) {
            $arrivalTime = Carbon::createFromTimestamp($arrivalTimestamp);
        } else {
            $arrivalTime = Carbon::parse($arrivalTimestamp);
        }

        $language = $request->getLanguage();

        $origin = new Station($origin, $language);
        $destination = new Station($destination, $language);

        $cacheKey = "lc2irail|connections|" . $origin->getId() . "|" . $destination->getId() . "|" . $departureTime->getTimestamp() . "|" . $arrivalTime->getTimestamp() . "|" . $language;

        if (Cache::has($cacheKey)) {
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
        $result = $repository->getConnections($origin->getUri(), $destination->getUri(), $departureTime, $arrivalTime, 3, 8, $language);

        Cache::put($cacheKey, $result, $result->getExpiresAt());

        return response()->json($result, 200)->withHeaders([
            'Expires' => $result->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $result->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' => $result->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag' => $result->getEtag()
        ]);

    }

    public function getConnectionsByDepartureNow(HyperrailRequest $request, $origin, $destination)
    {
        return redirect()->route("connections.byDeparture", ['origin' => $origin, 'destination' => $destination, 'timestamp' => Carbon::now()->toAtomString()]);
    }

    public function getConnectionsByArrivalNow(HyperrailRequest $request, $origin, $destination)
    {
        return redirect()->route("connections.byArrival", ['origin' => $origin, 'destination' => $destination, 'timestamp' => Carbon::now()->toAtomString()]);
    }


    public function getConnectionsByDeparture(HyperrailRequest $request, $origin, $destination, $timestamp)
    {
        if (is_numeric($timestamp)) {
            $requestTime = Carbon::createFromTimestamp($timestamp);
        } else {
            $requestTime = Carbon::parse($timestamp);
        }

        $language = $request->getLanguage();

        $origin = new Station($origin, $language);
        $destination = new Station($destination, $language);

        $cacheKey = "lc2irail|connections|" . $origin->getId() . "|" . $destination->getId() . "|departure|" . $requestTime->getTimestamp() . "|" . $language;

        if (Cache::has($cacheKey)) {
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
        $result = $repository->getConnectionsByDepartureTime($origin->getUri(), $destination->getUri(), $requestTime, $language);

        Cache::put($cacheKey, $result, $result->getExpiresAt());

        return response()->json($result, 200)->withHeaders([
            'Expires' => $result->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $result->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' => $result->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag' => $result->getEtag()
        ]);

    }

    public function getConnectionsByArrival(HyperrailRequest $request, $origin, $destination, $timestamp)
    {
        if (is_numeric($timestamp)) {
            $requestTime = Carbon::createFromTimestamp($timestamp);
        } else {
            $requestTime = Carbon::parse($timestamp);
        }

        $language = $request->getLanguage();

        $origin = new Station($origin, $language);
        $destination = new Station($destination, $language);

        $cacheKey = "lc2irail|connections|" . $origin->getId() . "|" . $destination->getId() . "|arrival|" . $requestTime->getTimestamp() . "|" . $language;

        if (Cache::has($cacheKey)) {
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
        $result = $repository->getConnectionsByArrivalTime($origin->getUri(), $destination->getUri(), $requestTime, $language);

        Cache::put($cacheKey, $result, $result->getExpiresAt());

        return response()->json($result, 200)->withHeaders([
            'Expires' => $result->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $result->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' => $result->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag' => $result->getEtag()
        ]);

    }

}