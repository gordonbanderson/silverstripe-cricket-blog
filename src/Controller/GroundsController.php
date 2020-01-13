<?php
namespace Suilven\CricketSite\Controller;

use Carbon\Carbon;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\SiteConfig\SiteConfig;
use Smindel\GIS\GIS;
use Suilven\CricketSite\Model\Competition;
use Suilven\CricketSite\Model\Ground;
use Suilven\CricketSite\Model\Player;
use Suilven\DarkSky\API\DarkSkyAPI;
use Suilven\DarkSky\Helper\WeatherDataPopulator;
use Suilven\DarkSky\Model\WeatherDataPoint;
use VertigoLabs\Overcast\Forecast;

class GroundsController extends \PageController
{
    private static $allowed_actions = [
        'forecast'
    ];

    public function forecast(HTTPRequest $request)
    {
        $config = SiteConfig::current_site_config();

        /** @var Ground $ground */
        $ground = $this->getGroundFromSlug($request);

        $location = $ground->Location;
        $coordinates = GIS::create($location)->coordinates;
        $longitude = $coordinates[0];
        $latitude = $coordinates[1];

        $api = new DarkSkyAPI();

        /** @var Forecast $weather */
        $weather = $api->getForecastAt($latitude, $longitude);

        /** @var WeatherDataPopulator $populator */
        $populator = new WeatherDataPopulator();

        /** @var WeatherDataPoint $currentWeatherRecord */
        $currentWeatherRecord = $populator->generatePopulatedRecord($weather->getCurrently());

        $dailyForecast = new ArrayList();
        foreach($weather->getDaily()->getData() as $forecastRecord)
        {
            $record = $populator->generatePopulatedRecord($forecastRecord);
            $dailyForecast->push($record);
        }


        // prepare data for Chart JS
        $rainChartData = [];
        $speedChartData = [];
        $temperatureChartData = [];
        $labels = [];
        $rainProbabilityData = [];
        $rainIntensityData = [];
        $windSpeeds = [];
        $windAngles = [];


        $hourlyForecast = new ArrayList();
        foreach($weather->getHourly()->getData() as $forecastRecord)
        {
            $record = $populator->generatePopulatedRecord($forecastRecord);
            $hourlyForecast->push($record);

            $parsed = Carbon::parse($record->When);
            $labels[] = $parsed->format('H:i');
            $windSpeeds[] = $record->WindSpeed;
            $windAngles[] = $record->WindBearing-90; // 0 in CSS is to the right, 0 in geo terms is up, aka north

            $rainProbabilityData[] = 100 * $record->PrecipitationProbablity;
            $rainIntensityData[] = 100 * $record->PrecipitationIntensity;
        }

        $speedChartData['labels'] = $labels;
        $speedChartData['labelString'] = 'Runs NOT';
        $speedChartData['datasets'] = [
            [
                'label' => 'Wind Speed and Direction',
                'data' => $windSpeeds,
                'angles' => $windAngles,
                'backgroundColor' => '#00B',
                'fill' => false
            ]
        ];
        $speedChartData['options'] = [
            'title' => [
                'display' => true,
                'text' => 'Wind Speed (m/s)'
            ]
        ];


        /*
         *       [ID] => 0
            [ClassName] => Suilven\DarkSky\Model\WeatherDataPoint
            [RecordClassName] => Suilven\DarkSky\Model\WeatherDataPoint
            [CloudCoverage] => 0.9
            [CurrentTemperature] => 7.33
            [DewPoint] => 4.39
            [Humidity] => 0.82
            [Icon] => cloudy
            [MaxTemperature] =>
            [MinTemperature] =>
            [FeelsLikeTemperature] => 3.23
            [MoonPhase] =>
            [PrecipitationIntensity] => 0.0526
            [PrecipitationProbablity] => 0.13
            [Visibility] => 10
            [When] => 2020-01-11 19:00:00
            [WindSpeed] => 17.61
            [WindBearing] => 236
         */

        $rainChartData['labels'] = $labels;
        $rainChartData['labelString'] = 'Runs NOT';
        $rainChartData['datasets'] = [
            [
                'label' => 'Probability',
                'data' => $rainProbabilityData,
                'backgroundColor' => '#007',
                'fill' => false
            ],
            [
                'label' => 'Intensity',
                'data' => $rainIntensityData,
                'backgroundColor' => 'maroon',
                'fill' => false
            ]
        ];

        $rainChartData['options'] = [
            'title' => [
                'display' => true,
                'text' => 'Rain Probability and Intensity'
            ]
        ];

        return [
            'Title' => 'Forecast: ' . $ground->Name,
            'CurrentWeather' => $currentWeatherRecord,
            'DailyForecast' => $dailyForecast,
            'HourlyForecast' => $hourlyForecast,
            'Ground' => $ground,
            'RainChartData' => json_encode($rainChartData),
            'WeatherChartData' => json_encode($speedChartData),
            'TemperatureChartData' => json_encode($temperatureChartData)
        ];
    }


    /**
     * @param HTTPRequest $request
     * @return \SilverStripe\ORM\DataObject
     */
    public function getGroundFromSlug(HTTPRequest $request): \SilverStripe\ORM\DataObject
    {
        $slug = $request->param('ID');
        $player = Ground::get()->filter(['Slug' => $slug])->first();
        if (!$player) {
            return $this->httpError(404, 'That ground with id ' . $slug . ' could not be found');
        }
        return $player;
    }

}
