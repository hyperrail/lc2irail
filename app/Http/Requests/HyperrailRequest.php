<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class HyperrailRequest extends Request
{

    public function getLanguage(): string
    {
        return $this->get('lang', 'en');
    }

    public function has($key)
    {
        return key_exists($key, $_GET);
    }

    public function get($key, $default = null)
    {
        if (key_exists($key, $_GET)) {
            return $_GET[$key];
        } else {
            return $default;
        }
    }
}