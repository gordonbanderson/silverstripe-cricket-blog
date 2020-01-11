<?php
namespace Suilven\CricketSite\Controller;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\SiteConfig\SiteConfig;
use Suilven\CricketSite\Model\Competition;
use Suilven\CricketSite\Model\InningsBattingEntry;
use Suilven\CricketSite\Model\InningsBowlingEntry;
use Suilven\CricketSite\Model\Player;

class StatisticsController extends \PageController
{
    private static $allowed_actions = [
        'index',
        'batting',
        'bowling'
    ];

    public function index(HTTPRequest $request)
    {
        $config = SiteConfig::current_site_config();
        $club = $config->ClubSiteIsFor();
        $players = $club->Players()->sort('Surname,FirstName');

        // @todo season filter
        $competitions = Competition::get()->sort('SortOrder');

        return [
            'Title' => 'Statistics',
            'Players' => $players,
            'Club' => $club,
            'Competitions' => $competitions
        ];
    }


    public function Link($action = null)
    {
        // Construct link with graceful handling of GET parameters
        $link = Controller::join_links('stats', $action);

        // Allow Versioned and other extension to update $link by reference.
        $this->extend('updateLink', $link, $action);

        return $link;
    }

    /**
     * Prepare to render batting information
     * @param HTTPRequest $request
     * @return array|void
     * @throws \SilverStripe\Control\HTTPResponse_Exception
     */
    public function batting(HTTPRequest $request)
    {
        $player = $this->getPlayerFromSlug($request);

        $params = $this->getURLParams();
        $competition = null;
        if (isset($params['OtherID'])) {
            $competition = $params['OtherID'];
            $competition = Competition::get()->filter(['Slug' => $competition])->first();
        }


        // sort the batting entries into time order by joining via innings and then match to get the When field
        $innings = InningsBattingEntry::get()->filter(['BatsmanID' => $player->ID])
            ->innerJoin('CricketInnings', '"CricketInningsBattingEntry"."InningsID" = "CricketInnings"."ID"')
            ->innerJoin('CricketMatch', '"CricketInnings"."MatchID" = "CricketMatch"."ID"');

        if (!empty($competition)) {
           // $inning = $innings->innerJoin('CricketCompetition', '"CricketMatch"."CompetitionID" = ' . $competition->ID);
        }

        $innings->sort('When');

        $labels = [];
        $runs = [];
        $fours = [];
        $sixes = [];

        $runColors = [];
        foreach ($innings as $inning) {
            $notOut = $inning->HowOut()->isNotOut();
            $notOutSuffix = $notOut ? '*' : '';
            $labels[] = ($inning->Innings()->getBowlingTeam()->Name) . ' (' . $inning->Runs . $notOutSuffix . ')';
            $runs[] = $inning->Runs;

            if (!$notOut) {
                $runColors[] = 'rgba(0, 0, 48, 0.6)';
            } else {
                $runColors[] = 'rgba(128, 0, 0, 0.6)';

            }
            $fours[] = $inning->Fours;
            $sixes[] = $inning->Sixes;
        }

        $chartData = [];
        $chartData['labels'] = $labels;
        $chartData['labelString'] = 'Runs';
        $chartData['datasets'] = [
            [
                'label' => 'Runs',
                'data' => $runs,
                'backgroundColor' => $runColors
            ]
        ];

        return [
            'Title' => 'Batting, ' . $player->DisplayName,
            'Player' => $player,
            'Innings' => $innings,
            'RunChartData' => json_encode($chartData)
        ];
    }

    private function getStandardChartOptions()
    {
        $json = '
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        ';

        return json_decode($json);
    }



    public function bowling(HTTPRequest $request)
    {
        $player = $this->getPlayerFromSlug($request);

        // sort the bowling entries into time order by joining via innings and then match to get the When field
        $innings = InningsBowlingEntry::get()->filter(['BowlerID' => $player->ID])
            ->innerJoin('CricketInnings', '"CricketInningsBowlingEntry"."InningsID" = "CricketInnings"."ID"')
            ->innerJoin('CricketMatch', '"CricketInnings"."MatchID" = "CricketMatch"."ID"')
            ->sort('When');

        $labels = [];
        $overs = [];
        $runs = [];
        $maidens = [];
        $wickets = [];

        $totalDeliveries = 0;
        $totalRuns = 0;
        $totalMaidens = 0;
        $totalWickets = 0;

        $runColors = [];
        foreach ($innings as $inning) {
            $labels[] = ($inning->Innings()->Team()->Name) . ' - ' . $inning->getDescription();
            $runs[] = $inning->Runs;
            $maidens[] = $inning->Maidens;
            $overs[] = $inning->Overs + ($inning->Balls)/10;
            $wickets[] = $inning->Wickets;

            $totalDeliveries = $totalDeliveries + 6*$inning->Overs + $inning->Balls;
            $totalRuns = $totalRuns + $inning->Runs;
            $totalWickets = $totalWickets + $inning->Wickets;
            $totalMaidens = $totalMaidens + $inning->Maidens;

            $runColors[] = 'rgba(0, 0, 48, 0.6)';

        }

        $chartDataRuns = [];
        $chartDataRuns['labels'] = $labels;
        $chartDataRuns['datasets'] = [
            [
                'label' => 'Runs',
                'data' => $runs,
                'backgroundColor' => $runColors
            ]
        ];



        $chartDataWickets = [];
        $chartDataWickets['labels'] = $labels;

        /*
         * @todo Start needs fixed to 0.0, and the step changed to 1
        $chartDataWickets['options'] = [
                'responsive' => true,
                'legend' =>
                    [
                        'position' => 'bottom'
                    ]

        ];
        */

        $chartDataWickets['datasets'] = [
            [
                'label' => 'Wickets',
                'data' => $wickets,
                'backgroundColor' => $runColors,

            ]
        ];



        return [
            'Title' => 'Bowling, ' . $player->DisplayName,
            'Player' => $player,
            'Innings' => $innings,
            'RunChartData' => json_encode($chartDataRuns),
            'WicketChartData' => json_encode($chartDataWickets),
            'TotalMaidens' => $totalMaidens,
            'TotalRuns' => $totalRuns,
            'TotalWickets' => $totalWickets,
            'TotalOvers' => floor($totalDeliveries/6),
            'TotalBalls' => $totalDeliveries % 6,
            'Average' => round($totalRuns/$totalWickets,2),
            'StrikeRate' => round($totalDeliveries/$totalWickets,2),
            'WicketsPerMatch' => round($totalWickets/($innings->Count()),2),
            'RPO' => round(6*($totalRuns/$totalDeliveries),2)
        ];

    }

    /**
     * @param HTTPRequest $request
     * @return \SilverStripe\ORM\DataObject
     */
    public function getPlayerFromSlug(HTTPRequest $request): \SilverStripe\ORM\DataObject
    {
        $slug = $request->param('ID');
        $player = Player::get()->filter(['Slug' => $slug])->first();
        if (!$player) {
            return $this->httpError(404, 'That player could not be found');
        }
        return $player;
    }
}
