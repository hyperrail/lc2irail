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
    $app->get('/{id:\d{9}}','Api\LiveboardController@getLiveboard');
    //$app->get('/{id:\d{9}}','Api\LiveboardController@getLiveboard');
    $app->get('/{station}','Api\LiveboardController@getLiveboardByName');
});


$app->group(['prefix' => '/vehicle'], function () use ($app) {
    $app->get('/{id}/{date:\d{8}}','Api\VehicleController@getVehicle');
});

$app->group(['prefix' => '/connections'], function () use ($app) {
    // DepartureConnection
    $app->get('/{stop:\d{7}}/{date:\d{8}}/{vehicle}','Api\LinkedConnectionController@getDepartureConnection');

    $app->get('/{origin:\d{9}}/{destination:\d{9}}/depart','Api\ConnectionsController@getConnectionsByDeparture');
    $app->get('/{origin:\d{9}}/{destination:\d{9}}/arrive','Api\ConnectionsController@getConnectionsByArrival');
});

$app->group(['prefix' => '/disturbances'], function () use ($app) {
    $app->get('/','Api\DisturbanceController@getDisturbances');
});

$app->group(['prefix' => '/linkedconnections'], function () use ($app) {
    $app->get('/{key}/{operator}/{value}','Api\LinkedConnectionController@getFilteredConnections');
    $app->get('/','Api\LinkedConnectionController@getConnections');
});