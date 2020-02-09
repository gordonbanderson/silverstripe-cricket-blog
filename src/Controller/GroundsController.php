<?php
namespace Suilven\CricketSite\Controller;

use Carbon\Carbon;
use SilverStripe\CMS\Controllers\CMSPagesController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;
use Smindel\GIS\GIS;
use Suilven\CricketSite\Model\Ground;
use Suilven\DarkSky\API\DarkSkyAPI;
use Suilven\DarkSky\Helper\WeatherDataPopulator;
use Suilven\DarkSky\Model\WeatherDataPoint;
use VertigoLabs\Overcast\Forecast;

class GroundsController extends \PageController
{
    private static $allowed_actions = [
        'forecast',
        'index'
    ];

    public function Breadcrumbs() {
        $items = new ArrayList();

        $items->push(new ArrayData([
            'Title' => 'Grounds',
            'MenuTitle' => 'Grounds',
            'Link' => 'grounds'
        ]));

        $items;
    }

    public function GroundsWithLocation()
    {
        //$players = Player::get()->filter('FirstName:not', ['Sam', null]);
        return Ground::get()->sort('Name')->filter('Location:not', null);
    }

    public function index(HTTPRequest $request)
    {
        $do = new DataObject();
        $do->Link = "/grounds/";
        $do->MenuTitle = 'Grounds';
        $do->isSelf = true;
        $this->AddBreadcrumbAfter($do);
        $grounds = $this->GroundsWithLocation();

        $groundsForMap = [];
        foreach($grounds as $ground) {
            $groundArr = [
              'Name' => $ground->Name,
              'Link' => $ground->Link(),
              'Latitude' => $ground->Latitude,
              'Longitude' => $ground->Longitude
            ];
            $groundsForMap[] = $groundArr;
        }

        return [
            'Grounds' => $grounds,
            'GroundData' => json_encode($groundsForMap)
        ];
    }


    public function forecast(HTTPRequest $request)
    {

        /** @var Ground $ground */
        $ground = $this->getGroundFromSlug($request);



        $do = new DataObject();
        $do->Link = "/grounds";
        $do->MenuTitle = "Grounds";
        $do->isSelf = false;
        $this->AddBreadcrumbAfter($do);


        $do = new DataObject();
        $do->Link = "/grounds/" . $ground->Slug;
        $do->MenuTitle = $ground->Title;
        $do->isSelf = true;
        $this->AddBreadcrumbAfter($do);


        $config = SiteConfig::current_site_config();


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
            $windSpeeds[] = $record->Rounded($record->WindSpeed, 1);
            $windAngles[] = $record->WindBearing+90; // 0 in CSS is to the right, 0 in geo terms is up, aka north

            $rainProbabilityData[] = 100 * $record->PrecipitationProbablity;
            $rainIntensityData[] = $record->Rounded(100*$record->PrecipitationIntensity, 2);
        }

        $speedChartData['labels'] = $labels;
        $speedChartData['datasets'] = [
            [
                'label' => 'Wind Speed and Direction',
                'data' => $windSpeeds,
                'angles' => $windAngles,
                'backgroundColor' => '#9f305b',
                'fill' => false
            ]
        ];
        $speedChartData['options'] = [
            'title' => [
                'display' => true,
                'text' => 'Wind Speed (m/s)'
            ],
            'scales' => [
                'yAxes' => [
                    ['ticks' => [
                        'beginAtZero' => true
                    ]
                    ]
                ]
            ],
            'legend' => [
                'display' => false
            ]
        ];


        $rainProbabilityChartData['labels'] = $labels;
        $rainProbabilityChartData['datasets'] = [
            [
                'label' => 'Probability',
                'data' => $rainProbabilityData,
                'backgroundColor' => '#000099',
                'borderColor' => '#000099',


                'fill' => false
            ]
        ];

        $rainProbabilityChartData['options'] = [
            'title' => [
                'display' => true,
                'text' => 'Rain Probability (%)'
            ],
            'scales' => [
                'yAxes' => [
                    ['ticks' => [
                        'beginAtZero' => true,
                        'max' => 100
                    ]
                    ]
                ]
            ],
            'legend' => [
                'display' => false
            ]
        ];

        $rainIntensityChartData['labels'] = $labels;
        $rainIntensityChartData['datasets'] = [
            [
                'label' => 'Probability',
                'data' => $rainIntensityData,
                'backgroundColor' => '#000099',
                'borderColor' => '#000099',


                'fill' => false
            ]
        ];

        $rainIntensityChartData['options'] = [
            'title' => [
                'display' => true,
                'text' => 'Rain Intensity (mm/h)'
            ],
            'scales' => [
                'yAxes' => [
                    ['ticks' => [
                        'beginAtZero' => true,
                    ]
                    ]
                ]
            ],
            'maintainAspectRatiomaintainAspectRatio' => false,
            'legend' => [
                'display' => false
            ]
        ];

        // encode this in a similar manner to multiple coordinates
        $groundArr = [
            'Name' => $ground->Name,
            'Link' => $ground->Link,
            'Latitude' => $ground->Latitude,
            'Longitude' => $ground->Longitude
        ];

        return [
            'Title' => 'Forecast: ' . $ground->Name,
            'CurrentWeather' => $currentWeatherRecord,
            'DailyForecast' => $dailyForecast,
            'HourlyForecast' => $hourlyForecast,
            'Ground' => $ground,
            'GroundData' => json_encode([$groundArr]),
            'RainProbabilityData' => json_encode($rainProbabilityChartData),
            'RainIntensityData' => json_encode($rainIntensityChartData),
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
