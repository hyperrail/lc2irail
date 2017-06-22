<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected static function getHafasID(int $id){
        return sprintf("%09d",$id);
    }
}
