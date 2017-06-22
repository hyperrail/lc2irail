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
        if ($this->has('timestamp')) {
            return Carbon::createFromTimestamp($this->get('timestamp'));
        } elseif ($this->has('date') && $this->has('time')) {
            return Carbon::createFromFormat('dmY Hi', date('dmY') . ' ' . $this->get('time'));
        } elseif ($this->has('time')) {
            return Carbon::createFromFormat('dmY Hi', $this->get('date') . ' ' . $this->get('time'));
        } else {
            return Carbon::now();
        }
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


}