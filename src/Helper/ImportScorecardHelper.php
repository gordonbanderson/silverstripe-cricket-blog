<?php

namespace Suilven\CricketSite\Helper;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use Suilven\CricketSite\Model\Club;
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
}
