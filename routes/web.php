<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->group(['prefix' => '/liveboard'], function () use ($app) {
    $app->get('/BE.NMBS.{id:\d{9}}','Api\LiveboardController@getLiveboard');
    $app->get('/{id:\d{7,9}}','Api\LiveboardController@getLiveboard');
    $app->get('/{station}','Api\LiveboardController@getLiveboardByName');
});