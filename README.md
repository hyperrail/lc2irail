# lc2irail
An API, compliant to the original iRail API, based on linkedconnections.

This project provides a lightweight API, based on the Lumen microframework. 
It aims at providing an API for Belgian rail, similar to the original iRail api, but with more efficient data structures and caching

## Installation
* Run composer install
* Run composer install for vendor/irail/stations
* Set APC to have at least 512MB of cache!

## Enpoints

Time can be passed on as  ISO8601 timestamps.

### Liveboard by station id
- liveboard/{id}
- liveboard/{id}/{timestamp}

Get a liveboard containing departures for a certain station. The ID is the 7 digit UIC ID, or the 9 digit HAFAS station ID

### Liveboard by station name
- liveboard/{name}
- liveboard/{name}/{timestamp}

This enpoint provides similar functionality as the endpoint described above, however, 
it allows searching stations by name.

### Vehicle
- vehicle/{id}/{yyyyMMdd}

Where id is the name of the train, e.g. IC837, and yyyyMMdd is the year, month, day of departure for which the train should be retrieved


### Route
- connections/{from}/{to}/departing/{timestamp}
- connections/{from}/{to}/arriving/{timestamp}
- connections/{from}/{to}/{departureTimestamp}/{arrivalTimestamp}
Where from and to can be either station ids or names.

This allows you to either get routes departing after or arriving before a certain time, 
or to get all routes departing after a certain time and arriving before an upper time bound.

## Language
- ?lang={ISO2 language}