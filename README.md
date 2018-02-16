# lc2irail
An API, compliant to the original iRail API, based on linkedconnections.

This project provides a lightweight API, based on the Lumen microframework. 
It aims at providing an API for Belgian rail, backwards compatible with the original iRail api.

## Installation
* Run composer install
* Run composer install for vendor/irail/stations
* Set APC to have at least 512MB of cache!

## Enpoints

Endpoints for backwards compatibility are between brackets.
Building new applications using backwards compatible endpoints is discouraged.
The new endpoints are more consistent, and backwards compatibility might be dropped in the future.

### Liveboard by station id
- liveboard/{id}
- liveboard/BE.NMBS.{id}
- liveboard/?id={id}

Get a liveboard containing departures for a certain station. The ID is the 9 digit HAFAS ID, as used in iRail/Stations.

### Liveboard by station name
- liveboard/{name} 
- (liveboard/?station={name})

This enpoint provides similar functionality as the endpoint described above, however, 
it allows searching stations by name.

### Vehicle
- vehicle/{name} 
- (Vehicle/?id={name})
- vehicle/BE.NMBS.{name} 
- (vehicle/?id=BE.NMBS.{name})

Where name is the name of the train, e.g. IC837.

### Route
- connections/{from}/{to}
- (connections/?from={from}&to={to})
Where from and to can be either station ids or names.

Not implemented yet


## Parameters

Parameters for backwards compatibility are between brackets.
Building new applications using backwards compatible parameters is discouraged.
The new parameters are more consistent, and backwards compatibility might be dropped in the future.

### Passing time
Time can be passed on in 3 formats:

- ?timestamp={unix timestamp}
- (?date={ddmmYY}&time={hhmm}) 
- (?time={hhmm}) *note: the current day will be used as date*

## Type of time
- ?datetimetype={arrival|departure|arr|dep}
- (?timeSel={arrival|departure)
- (?arrdep={arr|dep)

## Language
- ?lang={ISO2 language}