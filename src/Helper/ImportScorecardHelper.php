<?php

namespace Suilven\CricketSite\Helper;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SilverStripe\ORM\DataObject;
use Suilven\CricketSite\Model\Club;
use Suilven\CricketSite\Model\HowOut;
use Suilven\CricketSite\Model\InningsBattingEntry;
use Suilven\CricketSite\Model\Player;
use Suilven\CricketSite\Model\Team;
use Suilven\Sluggable\Helper\SluggableHelper;

class ImportScorecardHelper
{

    private $homeClub;
    private $awayClub;

    private $homeTeam;
    private $awayTeam;


    public function importScorecardFromSpreadsheet($spreadsheetFilePath)
    {
        $spreadsheet = IOFactory::load($spreadsheetFilePath);

        $this->parseClubAndTeams($spreadsheet);


        $this->parseAllInnings($spreadsheet);


    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseClubAndTeams(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getSheet(0);
        $homeClubName = $sheet->getCell('B1')->getCalculatedValue();
        $this->homeClub = $this->createOrGetClubBySlug( $homeClubName);
        error_log($this->homeClub);

        $awayClubName = $sheet->getCell('B2')->getCalculatedValue();
        $this->awayClub = $this->createOrGetClubBySlug( $awayClubName);
        error_log('HC: ' . $homeClubName);
        error_log('AC: ' . $awayClubName);

        $homeTeamName = $homeClubName . ' ' . $sheet->getCell('B3')->getCalculatedValue();
        $awayTeamName = $awayClubName . ' ' . $sheet->getCell('B4')->getCalculatedValue();
        error_log('HT: ' . $homeTeamName);
        error_log('AT: ' . $awayTeamName);

        $this->homeTeam = $this->createOrGetTeamBySlug($this->homeClub, $homeTeamName);
        $this->awayTeam = $this->createOrGetTeamBySlug($this->awayClub, $awayTeamName);
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

    private function createOrGetPlayer($team, $fieldValue)
    {
        $splits = explode(' ', $fieldValue);
        $params = [];

        // this logic may not work for some asian names, these will need fixed by hand
        $params['FirstName'] = $splits[0];
        $params['DisplayName'] = $splits[0];
        if (sizeof($splits ) > 1) {
            $params['Surname'] = $splits[1];
            $params['DisplayName'] = $splits[0] . ' ' . $splits[1];
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
            error_log('.... Model for ' . $fieldValue . ' not found');
            /** @var DataObject $instance */
            $instance = $clazz::create([$fieldName => $fieldValue]);
            error_log($fieldName . ' ' . $fieldValue);

            $instance->Name = $fieldValue;

            foreach($params as $key => $value) {
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
        $this->parseInnings($spreadsheet->getSheet(1));
        //$this->parseInnings($spreadsheet->getSheet(2));
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseInnings(Worksheet $sheet)
    {
        $battingClubName = $sheet->getCell('B1')->getCalculatedValue();
        $slugHelper = new SluggableHelper();
        $slug = $slugHelper->getSlug($battingClubName);
        $teamBatting = null;
        $teamBowling = null;
        switch ($slug) {
            case $this->homeClub->Slug:
                $teamBatting = $this->homeTeam;
                $teamBowling = $this->awayTeam;
                break;
            case $this->awayClub->Slug:
                $teamBatting = $this->awayTeam;
                $teamBowling = $this->homeTeam;
                break;
            default:
                user_error('The team batting could not be determined from the value ' . $battingClubName);
                break;
        }

        for ($i = 4; $i <= 14; $i++) {
            $playerName = $sheet->getCell('A' . $i)->getCalculatedValue();
            error_log($playerName);
            $this->createOrGetPlayer($teamBatting, $playerName);

            $howoutShortTitle1 = $sheet->getCell('B' . $i);
            $howOut1 = HowOut::get()->filter(['ShortTitle' => $howoutShortTitle1])->first();
            $fielder1Name = $sheet->getCell('C' . $i);
            $fielder1 = null;
            error_log('T1 fielder 1 name = *' . $fielder1Name . '*');
            if (strlen($fielder1Name) > 0) {
                error_log('NOT EMPTY!!!!');
                $fielder1 = $this->createOrGetPlayer($teamBowling, $fielder1Name);
            }

            $howoutShortTitle2 = $sheet->getCell('D' . $i);
            $howOut2 = HowOut::get()->filter(['ShortTitle' => $howoutShortTitle2])->first();
            $fielder2Name = $sheet->getCell('E' . $i);
            $fielder2 = null;
            if (strlen($fielder2Name) > 0) {
                $fielder2 = $this->createOrGetPlayer($teamBowling, $fielder2Name);
            }
            error_log($fielder2Name);
            $inningsEntry = new InningsBattingEntry();


        }
    }
}
