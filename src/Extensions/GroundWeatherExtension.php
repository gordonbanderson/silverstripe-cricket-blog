<?php
namespace Suilven\CricketSite\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\ArrayData;
use Smindel\GIS\GIS;
use Suilven\CricketSite\Model\Ground;
use Suilven\DarkSky\API\DarkSkyAPI;
use Suilven\DarkSky\Helper\WeatherDataPopulator;
use Suilven\DarkSky\Model\WeatherDataPoint;
use VertigoLabs\Overcast\Forecast;

class GroundWeatherExtension extends Extension {




    public function GroundWeather($slug)
    {
        $ground = Ground::get()->filter('Slug', $slug)->first();
        if (!$ground) {
            return '';
        }

        $location = $ground->Location;
        $coordinates = GIS::create($location)->coordinates;
        $longitude = $coordinates[0];
        $latitude = $coordinates[1];

        $api = new DarkSkyAPI();

        /** @var Forecast $weather */
        $weather = $api->getForecastAt($latitude, $longitude);

        /** @var WeatherDataPopulator $populator */
        $populator = new WeatherDataPopulator();

        /** @var WeatherDataPoint $record */
        $record = $populator->generatePopulatedRecord($weather->getCurrently());


        return $this->owner->customise(new ArrayData([
            'Name' => $ground->Name,
            'Slug' => $ground->Slug,
            'Weather' => $record
        ]))->renderWith('Includes/WeatherStationSmall');
    }
}
