<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\VehicleRepository;
use Carbon\Carbon;

class VehicleController extends Controller
{

    public function getVehicle(HyperrailRequest $request, string $id, string $date)
    {
        $language = $request->getLanguage();

        $cacheKey = "hyperrail|vehicle|$id|$language";
        if (Cache::has($cacheKey)) {
            /**
             * @var $vehicle Vehicle
             */
            $vehicle = Cache::get($cacheKey);
            return response()->json($vehicle, 200) ->withHeaders([
                'Expires' => $vehicle->getExpiresAt()->format('D, d M Y H:i:s e'),
                'Cache-Control' => 'Public, max-age=' . $vehicle->getExpiresAt()->diffInSeconds(new Carbon()),
                'Last-Modified' =>  $vehicle->getCreatedAt()->format('D, d M Y H:i:s e'),
                'ETag' => $vehicle->getEtag()
            ]);
        }

        /**
         * @var $repository LiveboardsRepositoryContract
         */
        $repository = new VehicleRepository();
        $vehicle = $repository->getVehicle($id, Carbon::createFromFormat($date,"yyyyMMdd"), $language);
        Cache::put($cacheKey, $vehicle, $vehicle->getExpiresAt());
        Log::info("LIVE, cached until " .  $vehicle->getExpiresAt());
        return response()->json($vehicle, 200) ->withHeaders([
            'Expires' => $vehicle->getExpiresAt()->format('D, d M Y H:i:s e'),
            'Cache-Control' => 'max-age=' . $vehicle->getExpiresAt()->diffInSeconds(new Carbon()),
            'Last-Modified' =>  $vehicle->getCreatedAt()->format('D, d M Y H:i:s e'),
            'ETag' => $vehicle->getEtag()
        ]);
    }

}