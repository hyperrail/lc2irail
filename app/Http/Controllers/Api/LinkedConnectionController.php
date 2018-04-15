<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\LinkedConnectionsRawRepositoryContract;
use App\Http\Repositories\LinkedConnectionsRepositoryContract;
use App\Http\Repositories\LiveboardsRepositoryContract;
use App\Http\Repositories\VehicleRepositoryContract;
use App\Http\Requests\HyperrailRequest;
use Cache;
use Carbon\Carbon;


class LinkedConnectionController extends Controller
{

    public function getConnections(HyperrailRequest $request, $timestamp = null)
    {
        if (is_numeric($timestamp)) {
            $requestTime = Carbon::createFromTimestamp($timestamp);
        } else {
            $requestTime = Carbon::parse($timestamp);
        }

        $repository = app(LinkedConnectionsRawRepositoryContract::class);
        $filtered = $repository->getRawLinkedConnections($requestTime);

        return response()->json($filtered['data'], 200)->withHeaders([
            'Expires' => $filtered['expiresAt']->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'Public, max-age=' . $filtered['expiresAt']->diffInSeconds(new Carbon()),
            'Last-Modified' => $filtered['createdAt']->format('D, d M Y H:i:s e'),
            'ETag' => $filtered['etag'],
        ]);
    }

    public function getDepartureConnection(HyperrailRequest $request, $stop, $date, $vehicle)
    {
        $repository = app(VehicleRepositoryContract::class);
        $vehicle = $repository->getVehicle($vehicle, $date, $request->getLanguage());
        $stops = $vehicle->getStops();
        foreach ($stops as $trainstop) {
            if ($trainstop->getStation()->getHid() == $stop) {
                return response()->json($trainstop, 200)->withHeaders([
                    'Expires' => $vehicle->getExpiresAt()->format('D, d M Y H:i:s e'),
                    'Cache-Control' => 'Public, max-age=' . $vehicle->getExpiresAt()->diffInSeconds(new Carbon()),
                    'Last-Modified' => $vehicle->getCreatedAt()->format('D, d M Y H:i:s e'),
                    'ETag' => $vehicle->getEtag()
                ]);
            }
        }
        abort(404);
    }


    public function getFilteredConnections(HyperrailRequest $request, String $key, String $operator, String $value, $timestamp = null)
    {
        if (is_numeric($timestamp)) {
            $requestTime = Carbon::createFromTimestamp($timestamp);
        } else {
            $requestTime = Carbon::parse($timestamp);
        }

        /**
         * @var $repository LiveboardsRepositoryContract
         */
        $repository = app(LinkedConnectionsRepositoryContract::class);
        $filtered = $repository->getFilteredLinkedConnections($requestTime, urldecode($key),
            urldecode($operator), urldecode($value));

        return response()->json($filtered['data'], 200)->withHeaders([
            'Expires' => $filtered['expiresAt']->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'Public, max-age=' . $filtered['expiresAt']->diffInSeconds(new Carbon()),
            'Last-Modified' => $filtered['createdAt']->format('D, d M Y H:i:s e'),
            'ETag' => $filtered['etag'],
        ]);
    }

}