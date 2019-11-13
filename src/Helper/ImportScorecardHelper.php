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
        $homeClubName = $sheet->getCell('B1');
        $this->homeClub = $this->createOrGetBySlug(Club::class, $homeClubName);
        $awayClubName = $sheet->getCell('B2');
        $this->awayClub = $this->createOrGetBySlug(Club::class, $awayClubName);
        error_log('HC: ' . $homeClubName);
        error_log('AC: ' . $awayClubName);

        $homeTeam = $homeClubName . ' ' . $sheet->getCell('B3');
        $awayTeam = $awayClubName . ' ' . $sheet->getCell('B4');
        error_log('HT: ' . $homeTeam);
        error_log('AT: ' . $awayTeam);
    }

    /**
     * @param DataObject $clazz
     * @param Cell $fieldValueCell
     */
    private function createOrGetBySlug($clazz, $fieldValueCell, $fieldName = 'Name')
    {
        $fieldValue = $fieldValueCell->getCalculatedValue();
        $slugHelper = new SluggableHelper();
        $slug = $slugHelper->getSlug($fieldValue);
        $model = $clazz::get()->filter(['Slug' => $slug])->first();

        if ($model) {
            error_log('.... Model for ' . $fieldValue . ' found');
            return $model;
        } else {
            error_log('.... Model for ' . $fieldValue . ' not found');
            /** @var DataObject $instance */
            $instance = $clazz::create([$fieldName => $fieldValue]);
            error_log($fieldName . ' ' . $fieldValue);

            $instance->Name = $fieldValue;
            $instance->write();
            return $instance;
        }
    }
}
