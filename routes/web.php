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
    return $router->app->version();
});

$router->group(['prefix' => '/liveboard'], function () use ($router) {

    $router->get('/{id:\d{9}}',
        [
            'as'   => 'liveboard.byIdNow',
            'uses' => 'Api\LiveboardController@getLiveboardNow'
        ]
    );


    $router->get('/{id:\d{7}}',
        [
            'as'   => 'liveboard.byUICNow',
            'uses' => 'Api\LiveboardController@getLiveboardNow'
        ]
    );

    $router->get('/{id:\d{9}}/{timestamp}',
        [
            'as'   => 'liveboard.byId',
            'uses' => 'Api\LiveboardController@getLiveboard'
        ]
    );

    $router->get('/{id:\d{7}}/{timestamp}',
        [
            'as'   => 'liveboard.byUIC',
            'uses' => 'Api\LiveboardController@getLiveboard'
        ]
    );

    $router->get('/{station:[^0-9]+}/{timestamp}',
        [
            'as'   => 'liveboard.byName',
            'uses' => 'Api\LiveboardController@getLiveboardByName'
        ]
    );
    $router->get('/{station:[^0-9]+}',
        [
            'as'   => 'liveboard.byNameNow',
            'uses' => 'Api\LiveboardController@getLiveboardByNameNow'
        ]
    );
});


$router->group(['prefix' => '/vehicle'], function () use ($router) {
    $router->get('/{id}',
        [
            'as'   => 'vehicle.byIdNow',
            'uses' => 'Api\VehicleController@getVehicleNow'
        ]
    );
    $router->get('/{id}/{date:\d{8}}',
        [
            'as'   => 'vehicle.byId',
            'uses' => 'Api\VehicleController@getVehicle'
        ]
    );
});

$router->group(['prefix' => '/connections'], function () use ($router) {
    $router->get('/{origin:\d{9}}/{destination:\d{9}}/departing/{timestamp}',
        [
            'as'   => 'connections.byDeparture',
            'uses' => 'Api\ConnectionsController@getConnectionsByDeparture'
        ]
    );
    $router->get('/{origin:\d{9}}/{destination:\d{9}}/departing',
        [
            'as'   => 'connections.byDepartureNow',
            'uses' => 'Api\ConnectionsController@getConnectionsByDepartureNow'
        ]
    );
    $router->get('/{origin:\d{9}}/{destination:\d{9}}/arriving/{timestamp}',
        [
            'as'   => 'connections.byArrival',
            'uses' => 'Api\ConnectionsController@getConnectionsByArrival'
        ]
    );
    $router->get('/{origin:\d{9}}/{destination:\d{9}}/arriving',
        [
            'as'   => 'connections.byArrivalNow',
            'uses' => 'Api\ConnectionsController@getConnectionsByArrivalNow'
        ]
    );
    $router->get('/{origin:\d{9}}/{destination:\d{9}}/{departureTimestamp}/{arrivalTimestamp}',
        [
            'as'   => 'connections.byBounds',
            'uses' => 'Api\ConnectionsController@getConnections'
        ]
    );
});

// DepartureConnection
$router->get('/connections/{stop:\d{7}}/{date:\d{8}}/{vehicle}',
    [
        'as'   => 'departureConnection',
        'uses' => 'Api\LinkedConnectionController@getDepartureConnection'
    ]
);


$router->group(['prefix' => '/disturbances'], function () use ($router) {
    $router->get('/', 'Api\DisturbanceController@getDisturbances');
});

$router->group(['prefix' => '/occupancy'], function () use ($router) {
    $router->get('/', 'Api\OccupancyController@getOccupancy');
    $router->post('/', 'Api\OccupancyController@postOccupancy');
});

$router->group(['prefix' => '/linkedconnections'], function () use ($router) {
    $router->get('/{key}/{operator}/{value}[/{timestamp}]', 'Api\LinkedConnectionController@getFilteredConnections');
    $router->get('/[{timestamp}]', 'Api\LinkedConnectionController@getConnections');
});