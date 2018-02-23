<?php

namespace App\Http\Repositories;

use App\Http\Models\LinkedConnectionPage;
use Cache;
use Carbon\Carbon;


/**
 * Class LinkedConnectionsRepositories
 * A read-only repository for realtime train data in linkedconnections format
 *
 * @package App\Http\Controllers
 */
class LinkedConnectionsWebRepository implements LinkedConnectionsRawRepositoryContract
{

    var $BASE_URL;

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->BASE_URL = env("LINKED_CONNECTIONS_BASE_URL", "https://graph.irail.be/sncb/connection");
    }

    /**
     * Build the URL to retrieve data from LinkedConnections
     *
     * @param Carbon $departureTime
     * @return string
     */
    private function getLinkedConnectionsURL(Carbon $departureTime): string
    {
        $departureTime = $departureTime->copy();
        $departureTime = $this->getRoundedDepartureTime($departureTime);
        return $this->BASE_URL . "?departureTime=" . date_format($departureTime, 'Y-m-d\TH:i:s') . '.000Z';
    }


    public function getRawLinkedConnections($pointer)
    {
        if ($pointer instanceof Carbon) {
            return $this->getRawLinkedConnectionsByUrl($this->getLinkedConnectionsURL($pointer));
        } elseif (is_string($pointer)) {
            return $this->getRawLinkedConnectionsByUrl($pointer);
        } else {
            throw new \InvalidArgumentException("Invalid argument type");
        }
    }

    private function getRawLinkedConnectionsByUrl($url)
    {
        $pageCacheKey = 'lcraw|' . $url;

        // Page (array including json, etag and expiresAt), kept for 2 hours so we can reuse the etag
        if (Cache::has($pageCacheKey)) {
            /**
             * @var $previousResponse LinkedConnectionPage
             */
            $previousResponse = Cache::get($pageCacheKey);

            // Check if cache is still valid
            $now = Carbon::now();
            if ($now->lessThan($previousResponse['expiresAt'])) {
                $raw = $previousResponse;
            }
        }

        // TODO: when there is a delay, subtract the delay from the departure or arrival time

        // If not valid, retrieve (but try Etag as well)
        if (!isset($raw)) {

            // No previous data, or previous data too old: re-validate
            $endpoint = $this->getLinkedConnectionsURL($url);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // If we have a cached old value, included header for conditional get
            if (isset($previousResponse)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'If-None-Match: "' . $previousResponse['etag'] . '"',
                ));
            }

            $headers = [];
            // this function is called by curl for each header received
            curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) // ignore invalid headers
                    {
                        return $len;
                    }

                    $name = strtolower(trim($header[0]));
                    if (!array_key_exists($name, $headers)) {
                        $headers[$name] = [trim($header[1])];
                    } else {
                        $headers[$name][] = trim($header[1]);
                    }

                    return $len;
                }
            );

            $data = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $etag = $headers['etag'][0];
            if (starts_with($etag, 'W/')) {
                $etag = substr($etag, 2);
            }
            $etag = trim($etag, '"');
            $expiresAt = Carbon::createFromTimestamp(strtotime($headers['expires'][0]));

            if (isset($previousResponse) && ($info['http_code'] == 304 || $etag == $previousResponse['etag'])) {
                // ETag unchanged, or header status code indicating no change
                // Just keep the raw data
                // No body is sent in this case!
            } else {
                $json = json_decode($data, true);

                $raw = ['data' => $json['@graph'], 'etag' => $etag, 'expiresAt' => $expiresAt, 'createdAt' => new Carbon(), 'next' => $json['hydra:next'], 'previous' => $json['hydra:previous']];

                Cache::put($pageCacheKey, $raw, $expiresAt);
            }

        }

        return $raw;
    }

    private function getRoundedDepartureTime(Carbon $departureTime): Carbon
    {
        return $departureTime->subMinute($departureTime->minute % 10)->second(0);
    }
}
