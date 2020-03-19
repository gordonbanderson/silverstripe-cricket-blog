<?php

namespace Suilven\CricketSite\Helper;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SilverStripe\ORM\DataObject;
use Suilven\CricketSite\Model\Club;
use Suilven\CricketSite\Model\Competition;
use Suilven\CricketSite\Model\FallOfWicket;
use Suilven\CricketSite\Model\Ground;
use Suilven\CricketSite\Model\HowOut;
use Suilven\CricketSite\Model\Innings;
use Suilven\CricketSite\Model\InningsBattingEntry;
use Suilven\CricketSite\Model\InningsBowlingEntry;
use Suilven\CricketSite\Model\Match;
use Suilven\CricketSite\Model\Player;
use Suilven\CricketSite\Model\Season;
use Suilven\CricketSite\Model\Team;
use Suilven\Sluggable\Helper\SluggableHelper;

class ImportScorecardHelper
{

    private $homeClub;
    private $awayClub;

    private $homeTeam;
    private $awayTeam;

    private $ground;
    private $match;
    private $competitionName;


    public function importScorecardFromSpreadsheet($spreadsheetFilePath)
    {
        $spreadsheet = IOFactory::load($spreadsheetFilePath);

        $this->checkScorecard($spreadsheet);
        $this->parseOverview($spreadsheet);
        $this->parseAllInnings($spreadsheet);
    }


    public function checkScorecard($spreadsheet)
    {
        $errors = false;

        // check clubs
        $sheet = $spreadsheet->getSheet(0);
        $homeClubName = $sheet->getCell('B1')->getCalculatedValue();
        if (Club::get()->filter(['Name' => $homeClubName] )->count() != 1) {
            error_log('HOME CLUB "' . $homeClubName . '" not found');
            $errors = true;
        }

        $awayClubName = $sheet->getCell('B2')->getCalculatedValue();
        $this->awayClub = $this->createOrGetClubBySlug($awayClubName);
        if (Club::get()->filter(['Name' => $awayClubName] )->count() != 1) {
            error_log('AWAY CLUB "' . $awayClubName . '" not found');
            $errors = true;
        }

        // check teams
        $homeTeamName = $homeClubName . ' ' . $sheet->getCell('B3')->getCalculatedValue();
        $awayTeamName = $awayClubName . ' ' . $sheet->getCell('B4')->getCalculatedValue();

        if (Team::get()->filter(['Name' => $homeTeamName] )->count() != 1) {
            error_log('COUNT: ' . Team::get()->filter(['Name' => $homeTeamName] )->count() );
            error_log('HOME TEAM "' . $homeTeamName . '" not found');
            $errors = true;
        }

        if (Team::get()->filter(['Name' => $awayTeamName] )->count() != 1) {
            error_log('AWAY TEAM "' . $awayTeamName . '" not found');
            $errors = true;
        }


        $groundName = $sheet->getCell('B5')->getCalculatedValue();
        if (Ground::get()->filter(['Name' => $groundName])->count() != 1) {
            error_log('GROUND "' . $groundName . ' " not found');
            $errors = true;
        }





        // @todo Season is missing
        $competitionName = $sheet->getCell('B6')->getCalculatedValue();
        $year = $sheet->getCell('B7')->getCalculatedValue();
        $season = Season::get()->filter(['Name' => $year])->first();
        if (empty($season)) {
            error_log('SEASON " ' . $year . '" does not exist');
            $errors = true;
        }

        $competitionName = $year . ' ' . $competitionName;
        if ($season->Competitions()->filter(['Title' => $competitionName])->count() != 1) {
            error_log('COMPETITION "' . $competitionName . '" not found');
            $errors = true;
        }

        $errors = $errors || $this->checkPlayersBatting($spreadsheet->getSheet(1));
        $errors = $errors || $this->checkPlayersBatting($spreadsheet->getSheet(2));
        $errors = $errors || $this->checkPlayersBowling($spreadsheet->getSheet(1));
        $errors = $errors || $this->checkPlayersBowling($spreadsheet->getSheet(2));
        $errors = $errors || $this->checkPlayersFOW($spreadsheet->getSheet(1));
        $errors = $errors || $this->checkPlayersFOW($spreadsheet->getSheet(2));

        if ($errors) {
            error_log('Errors were found');
            die;
        }

    }


    private function checkPlayersBatting($sheet)
    {
        $errors = false;
        for ($i = 4; $i <= 14; $i++) {
            $playerName = $sheet->getCell('A' . $i)->getCalculatedValue();
            error_log('BATTING PLAYER: ' . $playerName);
            if (!empty($playerName) && Player::get()->filter(['DisplayName' => $playerName])->count() != 1) {
                error_log('BATTING PLAYER "' . $playerName . '" NOT FOUND');
                $errors = true;
            }
        }

        return $errors;
    }


    private function checkPlayersBowling($sheet)
    {
        $errors = false;

        for ($i = 44; $i <= 54; $i++) {
            $playerName = $sheet->getCell('A' . $i)->getCalculatedValue();

            error_log('BOWLING PLAYER: ' . $playerName);
            if (!empty($playerName) && Player::get()->filter(['DisplayName' => $playerName])->count() != 1) {
                error_log('BOWLING PLAYER "' . $playerName . '" NOT FOUND');
                $errors = true;
            }
        }

        return $errors;
    }


    private function checkPlayersFOW($sheet)
    {
        $errors = false;


        for ($i = 30; $i <= 39; $i++) {
            $playerName = $sheet->getCell('B' . $i)->getCalculatedValue();

            error_log('FOW PLAYER: ' . $playerName);
            if (!empty($playerName) && Player::get()->filter(['DisplayName' => $playerName])->count() != 1) {
                error_log('BOWLING PLAYER "' . $playerName . '" NOT FOUND');
                $errors = true;
            }
        }

        return $errors;
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseOverview(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getSheet(0);
        $homeClubName = $sheet->getCell('B1')->getCalculatedValue();
        $this->homeClub = $this->createOrGetClubBySlug($homeClubName);
        error_log($this->homeClub);

        $awayClubName = $sheet->getCell('B2')->getCalculatedValue();
        $this->awayClub = $this->createOrGetClubBySlug($awayClubName);
        error_log('HC: ' . $homeClubName);
        error_log('AC: ' . $awayClubName);

        $homeTeamName = $homeClubName . ' ' . $sheet->getCell('B3')->getCalculatedValue();
        $awayTeamName = $awayClubName . ' ' . $sheet->getCell('B4')->getCalculatedValue();
        error_log('HT: ' . $homeTeamName);
        error_log('AT: ' . $awayTeamName);

        $this->homeTeam = $this->createOrGetTeamBySlug($this->homeClub, $homeTeamName);
        $this->awayTeam = $this->createOrGetTeamBySlug($this->awayClub, $awayTeamName);

        $groundName = $sheet->getCell('B5')->getCalculatedValue();
        $this->ground = $this->createOrGetGroundBySlug($this->homeClub, $groundName);

        $seasonName = $sheet->getCell('B7')->getCalculatedValue();
        $competitionName = $seasonName . ' ' . $sheet->getCell('B6')->getCalculatedValue();

        $this->competition = $this->createOrGetCompetitionBySlug($competitionName);

        error_log('SEASON: ' . $seasonName);
        $when = $sheet->getCell('B8')->getCalculatedValue() . ' 12:00:00';

        $resultDescription = $sheet->getCell('B9')->getCalculatedValue();

        error_log('**** RESULT DEC ****' . $resultDescription);


        $this->match = new Match();
        $this->match->Duration = '08:00:00';
        $this->match->TimeFrameType = 'Duration';
        error_log('WHEN: ' . $when);

        $this->match->Competition = $this->competition;
        $this->match->Ground = $this->ground;
        $this->match->HomeTeam = $this->homeTeam;
        $this->match->AwayTeam = $this->awayTeam;
        $this->match->Summary = $resultDescription;

        //$when = '19/5/2018';
        $this->match->When = $when;
        $this->match->StartDateTime = $when;

        error_log('WHEN=' . $when);
        $this->match->write();

        $this->competition->Matches()->add($this->match);

        /**
         * @todo
         *
         * Result,When,Status
         * HomeTeam
         * AwayTeam
         * HomeTeamPlayers
         * AwayTeamPlayers
         *
         */

    }


    /**
     * @param $fieldValue
     * @return Club
     */
    private function createOrGetClubBySlug($fieldValue)
    {
        return $this->createOrGetBySlug(Club::class, $fieldValue, 'Name');
    }


    /**
     * @param $fieldValue
     * @return Club
     */
    private function createOrGetCompetitionBySlug($fieldValue)
    {
        return $this->createOrGetBySlug(Competition::class, $fieldValue, 'Title');
    }


    /**
     * @param Club $club
     * @param $fieldValue
     * @return Team DataObject
     */
    private function createOrGetTeamBySlug($club, $fieldValue)
    {
        $team = $this->createOrGetBySlug(Team::class, $fieldValue, 'Name',
            ['ClubID' => $club->ID]);
        return $team;
    }

    private function createOrGetGroundBySlug($club, $fieldValue)
    {
        $team = $this->createOrGetBySlug(Ground::class, $fieldValue, 'Name',
            ['ClubID' => $club->ID]);
        return $team;
    }

    // @todo Filter by club
    private function createOrGetPlayer($team, $fieldValue)
    {
        $splits = explode(' ', $fieldValue);
        $params = [];

        // this logic may not work for some asian names, these will need fixed by hand
        $params['FirstName'] = $splits[0];
        $params['DisplayName'] = $splits[0];
        if (sizeof($splits) > 1) {
            $params['Surname'] = $splits[1];
            $params['DisplayName'] = implode(' ', $splits);
        }

        $player = $this->createOrGetBySlug(Player::class, $fieldValue, 'DisplayName', $params);

        // this does not create multiple duplicates, if the relationship exists another is not added
        $player->Clubs()->add($team->Club());
        $player->write();

        //
        return $player;
    }

    /**
     * @param DataObject $clazz
     * @param Cell $fieldValueCell
     */
    private function createOrGetBySlug($clazz, $fieldValue, $fieldName = 'Name', $params = [])
    {
        $slugHelper = new SluggableHelper();
        $slug = $slugHelper->getSlug($fieldValue);
        $model = $clazz::get()->filter(['Slug' => $slug])->first();


        error_log(print_r($params, 1));

        if ($model) {
            error_log('.... Model for ' . $fieldValue . ' found');
            return $model;
        } else {
            error_log('.... Model ' . $clazz .' for ' . $fieldValue . ' not found');
            //die;
            /** @var DataObject $instance */
            $instance = $clazz::create([$fieldName => $fieldValue]);
            error_log($fieldName . ' ' . $fieldValue);

            $instance->Name = $fieldValue;

            foreach ($params as $key => $value) {
                $instance->$key = $value;
                error_log("Set {$key} to {$value}");
            }
            $instance->write();
            return $instance;
        }
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseAllInnings(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet)
    {
        $match = new Match();

        $firstInnings = $this->parseInnings($spreadsheet->getSheet(1), 1);
        $match->Innings()->add($firstInnings);
        $secondInnings = $this->parseInnings($spreadsheet->getSheet(2), 2);
        $match->Innings()->add($secondInnings);
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseInnings(Worksheet $sheet, $order)
    {
        $innings = new Innings();
        $innings->SortOrder = $order;

        $innings->Byes = $sheet->getCell('F17')->getCalculatedValue();
        $innings->LegByes = $sheet->getCell('F18')->getCalculatedValue();
        $innings->NoBalls = $sheet->getCell('F19')->getCalculatedValue();
        $innings->Wides = $sheet->getCell('F20')->getCalculatedValue();
        $innings->PenaltyRuns = $sheet->getCell('F21')->getCalculatedValue();

        $innings->TotalRuns = $sheet->getCell('F23')->getCalculatedValue();
        $innings->TotalWickets = $sheet->getCell('F24')->getCalculatedValue();

        $oversAndBalls = $sheet->getCell('F25')->getCalculatedValue();
        $overs = (int) $oversAndBalls;
        $balls = ($oversAndBalls - $overs) * 10;

        $innings->TotalOvers = $overs;
        $innings->TotalBalls = $balls;

        // @todo Overs

        /*
         *     private static $db = [
        'Wides' => 'Int',
        'NoBalls' => 'Int',
        'LegByes' => 'Int',
        'BattingSummary' => 'Text',
        'BowlingSummary' => 'Text',
        'TotalRuns' => 'Int',
        'TotalWickets' => 'Int'
    ];

    private static $has_one = [
       // 'Team' => Team::class,
       // 'Match' => Match::class
    ];

    private static $has_many = [
        'InningsBattingEntries' => InningsBattingEntry::class,
        'InningsBowlingEntries' => InningsBowlingEntry::class,
    ];

    private static $belongs_to = [
        Match::class
    ];
         */

        $battingClubName = $sheet->getCell('B1')->getCalculatedValue();
        $slugHelper = new SluggableHelper();
        $slug = $slugHelper->getSlug($battingClubName);
        $teamBatting = null;
        $teamBowling = null;
        $homeTeamBatting = true;

        error_log('SLUG:    *' . $slug . '*');
        error_log('HC SLUG: *' . $this->homeClub->Slug . '*');
        error_log('AC SLUG: ' . $this->awayClub->Slug);
        switch ($slug) {
            case $this->homeClub->Slug:
                $teamBatting = $this->homeTeam;
                $teamBowling = $this->awayTeam;
                break;
            case $this->awayClub->Slug:
                $teamBatting = $this->awayTeam;
                $teamBowling = $this->homeTeam;
                $homeTeamBatting = false;
                break;
            default:
                user_error('The team batting could not be determined from the value ' . $battingClubName);
                die;
                break;
        }

        $innings->Team = $teamBatting;
        $innings->Match = $this->match;

        $innings->write();


        $this->parseBattingScorecard($sheet, $teamBatting, $homeTeamBatting, $teamBowling, $innings);
        $this->parseFallOfWickets($sheet, $teamBatting, $innings);
        $this->parseBowlingScorecard($sheet, $teamBatting, $homeTeamBatting, $teamBowling, $innings);

        return $innings;
    }

    public function parseBowlingScorecard(Worksheet $sheet, $teamBatting, bool $homeTeamBatting, $teamBowling, Innings $innings)
    {
        for ($i = 44; $i <= 54; $i++) {
            $bowlerName = $sheet->getCell('A' . $i)->getCalculatedValue();
            if (strlen($bowlerName) > 0) {
                $overs = $sheet->getCell('B' . $i)->getCalculatedValue();
                $maidens = $sheet->getCell('C' . $i)->getCalculatedValue();
                $runs = $sheet->getCell('D' . $i)->getCalculatedValue();
                $wickets = $sheet->getCell('E' . $i)->getCalculatedValue();
                $wides = $sheet->getCell('F' . $i)->getCalculatedValue();
                $noballs = $sheet->getCell('G' . $i)->getCalculatedValue();
                $fours = $sheet->getCell('H' . $i)->getCalculatedValue();
                $sixes = $sheet->getCell('I' . $i)->getCalculatedValue();

                $bowler = $this->createOrGetPlayer($teamBowling, $bowlerName);
                $bowlingEntry = new InningsBowlingEntry();
                $bowlingEntry->InningsID = $innings->ID;
                $bowlingEntry->BowlerID = $bowler->ID;
                $bowlingEntry->Overs = floor($overs);
                $bowlingEntry->Balls = 10 * ($overs - floor($overs));
                $bowlingEntry->Maidens = $maidens;
                $bowlingEntry->Runs = $runs;
                $bowlingEntry->Wickets = $wickets;
                $bowlingEntry->Wides = $wides;
                $bowlingEntry->NoBalls = $noballs;
                $bowlingEntry->Fours = $fours;
                $bowlingEntry->Sixes = $sixes;
                $bowlingEntry->SortOrder = $i-43;
                $bowlingEntry->write();
            }
        }
    }


    public function parseFallOfWickets(Worksheet $sheet, $teamBatting,  Innings $innings)
    {
        for ($i = 30; $i <= 39; $i++) {
            $runs = $sheet->getCell('A' . $i)->getCalculatedValue();
            $playerName = $sheet->getCell('B' . $i)->getCalculatedValue();

            if (strlen($playerName) > 0) {
                $batsman = $this->createOrGetPlayer($teamBatting, $playerName);
                $fow = new FallOfWicket();
                $fow->Runs = $runs;
                $fow->BatsmanID = $batsman->ID;
                $fow->InningsID = $innings->ID;
                $fow->write();
            }

        }
    }

            /**
     * @param Worksheet $sheet
     * @param $teamBatting
     * @param bool $homeTeamBatting
     * @param $teamBowling
     * @param Innings $innings
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseBattingScorecard(Worksheet $sheet, $teamBatting, bool $homeTeamBatting, $teamBowling, Innings $innings)
    {
        for ($i = 4; $i <= 14; $i++) {
            $playerName = $sheet->getCell('A' . $i)->getCalculatedValue();
            error_log($playerName);


            if (empty($playerName)) {
                error_log('Batsmen exhausted');
                break;
            }

            $batsman = $this->createOrGetPlayer($teamBatting, $playerName);

            if ($homeTeamBatting) {
                $this->match->HomeTeamPlayers()->add($batsman);
            } else {
                $this->match->AwayTeamPlayers()->add($batsman);
            }

            $howoutShortTitle1 = $sheet->getCell('B' . $i);
            $howOut1 = HowOut::get()->filter(['ShortTitle' => $howoutShortTitle1])->first();
            $fielder1Name = $sheet->getCell('C' . $i);
            $fielder1 = null;
            error_log('F1 fielder 1 name = *' . $fielder1Name . '*');
            if (strlen($fielder1Name) > 0) {
                error_log('NOT EMPTY!!!!');
                $fielder1 = $this->createOrGetPlayer($teamBowling, $fielder1Name);
                if ($homeTeamBatting) {
                    $this->match->AwayTeamPlayers()->add($fielder1);
                } else {
                    $this->match->HomeTeamPlayers()->add($fielder1);
                }
            }

            $howoutShortTitle2 = $sheet->getCell('D' . $i);
            $howOut2 = HowOut::get()->filter(['ShortTitle' => $howoutShortTitle2])->first();
            $fielder2Name = $sheet->getCell('E' . $i);
            error_log('F2 fielder 1 name = *' . $fielder1Name . '*');

            $fielder2 = null;
            if (strlen($fielder2Name) > 0) {
                $fielder2 = $this->createOrGetPlayer($teamBowling, $fielder2Name);
                if ($homeTeamBatting) {
                    $this->match->AwayTeamPlayers()->add($fielder2);
                } else {
                    $this->match->HomeTeamPlayers()->add($fielder2);
                }
            }
            error_log($fielder2Name);
            $inningsEntry = new InningsBattingEntry();
            $inningsEntry->SortOrder = $i-3;
            $inningsEntry->Batsman = $batsman;
            $inningsEntry->FieldingPlayer1 = $fielder1;
            $inningsEntry->FieldingPlayer2 = $fielder2;
            $inningsEntry->HowOut = !empty($howOut1) ? $howOut1 : $howOut2;
            $inningsEntry->Runs = $sheet->getCell('F' . $i)->getCalculatedValue();
            $inningsEntry->BallsFaced = $sheet->getCell('G' . $i)->getCalculatedValue();
            $inningsEntry->Minutes = null;
            $inningsEntry->Fours = $sheet->getCell('H' . $i)->getCalculatedValue();
            $inningsEntry->Sixes = $sheet->getCell('I' . $i)->getCalculatedValue();

            $innings->InningsBattingEntries()->add($inningsEntry);

        }
    }
}
