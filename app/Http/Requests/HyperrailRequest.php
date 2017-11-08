<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Http\Request;

class HyperrailRequest extends Request
{
    public static $TYPE_DEPARTURE = 0;
    public static $TYPE_ARRIVAL = 1;

    public function getDateTime(): Carbon
    {
        // Timestamp (or no time definition at all) should be the used.
        // Any other time/date parameters are for some backwards compatibility and will be removed in the future.
        if ($this->has('timestamp')) {
            $timestamp = Carbon::createFromTimestamp($this->get('timestamp'));
        } elseif ($this->get('time')) {
            $timestamp = Carbon::createFromFormat('dmY Hi', date('dmY') . ' ' . $this->get('time'));
        } elseif ($this->has('date') && $this->has('time')) {
            $timestamp = Carbon::createFromFormat('dmY Hi', $this->get('date') . ' ' . $this->get('time'));
        } else {
            $timestamp = Carbon::now();
        }

        return $this->getRoundedDepartureTime($timestamp);
    }

    public function getDateTimeType(): int
    {
        foreach (['arrdep', 'timeSel', 'timesel', 'datetimetype'] as $possibleFieldName) {
            if ($this->has($possibleFieldName)) {
                if (starts_with($this->get($possibleFieldName, 'departure'), 'a')) {
                    return self::$TYPE_ARRIVAL;
                } else {
                    return self::$TYPE_DEPARTURE;
                }
            }
        }
        return self::$TYPE_DEPARTURE;
    }

    public function getLanguage(): string
    {
        return $this->get('lang', 'en');
    }

    private function getRoundedDepartureTime(Carbon $departureTime) : Carbon {
        return $departureTime->subMinute($departureTime->minute % 10)->second(0);
    }

    public function has($key)
    {
        return key_exists($key, $_GET);
    }

    public function get($key, $default=null){
        if (key_exists($key,$_GET)){
            return $_GET[$key];
        } else {
            return $default;
        }
    }
}