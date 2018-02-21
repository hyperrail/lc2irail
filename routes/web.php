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

$router->get('/', function () use ($router) {
    return $router->version();
});

$router->group(['prefix' => '/liveboard'], function () use ($router) {
    $router->get('/{id:\d{9}}','Api\LiveboardController@getLiveboard');
    //$app->get('/{id:\d{9}}','Api\LiveboardController@getLiveboard');
    $router->get('/{station}','Api\LiveboardController@getLiveboardByName');
});


$router->group(['prefix' => '/vehicle'], function () use ($router) {
    $router->get('/{id}/{date:\d{8}}','Api\VehicleController@getVehicle');
});

$router->group(['prefix' => '/connections'], function () use ($router) {
    // DepartureConnection
    $router->get('/{stop:\d{7}}/{date:\d{8}}/{vehicle}','Api\LinkedConnectionController@getDepartureConnection');

    $router->get('/{origin:\d{9}}/{destination:\d{9}}/depart','Api\ConnectionsController@getConnectionsByDeparture');
    $router->get('/{origin:\d{9}}/{destination:\d{9}}/arrive','Api\ConnectionsController@getConnectionsByArrival');
});

$router->group(['prefix' => '/disturbances'], function () use ($router) {
    $router->get('/','Api\DisturbanceController@getDisturbances');
});

$router->group(['prefix' => '/linkedconnections'], function () use ($router) {
    $router->get('/{key}/{operator}/{value}','Api\LinkedConnectionController@getFilteredConnections');
    $router->get('/','Api\LinkedConnectionController@getConnections');
});