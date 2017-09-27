<?php

namespace App\Http\Models;

use irail\stations\Stations;

/**
 * Class Station
 */
class Station implements \JsonSerializable
{
    private $id;

    private $uri;
    private $defaultName;
    private $localizedName;

    private $latitude;
    private $longitude;

    private $countryCode;
    private $countryURI;

    /**
     * Station constructor.
     *
     * @param string $id   The 9 digit HAFAS ID for this station, or the URI.
     * @param string $lang The language for localized name. Empty if localization isn't required.
     */
    public function __construct(
        string $id,
        string $lang = ''
    ) {
        $iRailStation = Stations::getStationFromID($id);
        $this->uri = $iRailStation->{'@id'};
        $this->id = 'BE.NMBS.' . basename($this->uri);
        $this->defaultName = $iRailStation->name;

        $this->localizedName = $this->defaultName;
        if ($lang != '' && property_exists($iRailStation,'alternative')) {
            foreach ($iRailStation->alternative as $alternative) {
                if ($alternative->{'@language'} == $lang) {
                    $this->localizedName = $alternative->{'@value'};
                }
            }
        }

        $this->longitude = $iRailStation->longitude;
        $this->latitude = $iRailStation->latitude;

        $this->countryURI = $iRailStation->country;
        switch ($this->countryURI) {
            case 'http://sws.geonames.org/2802361/':
                $this->countryCode = 'be';
                break;
            case 'http://sws.geonames.org/2635167/':
                $this->countryCode = 'en';
                break;
            case 'http://sws.geonames.org/2921044/':
                $this->countryCode = 'de';
                break;
            case 'http://sws.geonames.org/2960313/':
                $this->countryCode = 'lu';
                break;
            case 'http://sws.geonames.org/2750405/':
                $this->countryCode = 'nl';
                break;
            case 'http://sws.geonames.org/3017382/':
                $this->countryCode = 'fr';
                break;
        }
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDefaultName(): string
    {
        return $this->defaultName;
    }

    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    public function getLatitude(): double
    {
        return $this->latitude;
    }

    public function getLongitude(): double
    {
        return $this->longitude;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getCountryURI(): string
    {
        return $this->countryURI;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }

}