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

Time can be passed on as a standard time representation, for example ISO8601 or epoch timestamps.

### Liveboard by station id
- liveboard/{id}

Get a liveboard containing departures for a certain station. The ID is the 9 digit universal station ID, as used in iRail/Stations.

### Liveboard by station name
- liveboard/{name} 

This enpoint provides similar functionality as the endpoint described above, however, 
it allows searching stations by name.

### Vehicle
- vehicle/{id}
(- vehicle/BE.NMBS.{id})
Where id is the name of the train, e.g. IC837.

### Route
- connections/{from}/{to}/departing/{timestamp}
- connections/{from}/{to}/arriving/{timestamp}
- connections/{from}/{to}/{departureTimestamp}/{arrivalTimestamp}
Where from and to can be either station ids or names.

This allows you to either get routes departing after or arriving before a certain time, 
or to get all routes departing after a certain time and arriving before an upper time bound.

## Language
- ?lang={ISO2 language}