<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class Innings extends DataObject
{
    private static $table_name = 'CricketInnings';

    private static $db = [
        'BattingSummary' => 'Text',
        'BowlingSummary' => 'Text',
        'TotalRuns' => 'Int',
        'TotalWickets' => 'Int'
    ];

    private static $has_one = [
        'Team' => Team::class,
        'Match' => Match::class
    ];

    private static $has_many = [
        'InningsEntries' => InningsEntry::class
    ];

    private static $belongs_to = [
        Match::class
    ];


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $teams = new ArrayList([
           $this->Match()->HomeTeam(),
           $this->Match()->AwayTeam()
        ]);
        $teamField = DropdownField::create('Team', 'Batting Team', $teams->
        sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select batting team --');
        $fields->addFieldToTab('Root.Main', $teamField);

        $fields->addFieldToTab('Root.Entries', GridField::create(
            'InningsEntries',
            'Individual Innings',
            $this->InningsEntries(),
            GridFieldConfig_RecordEditor::create()
        ));

        return $fields;
    }

    public function getTitle()
    {
        return $this->Name;
    }

    // cannot get this to work for some reason, the trait for image tweaking is missing and the HTML needs to be converted
    // and not returned raw
    public function getPhotoThumbnail() {
        // display a thumbnail of the Image from the has_one relation

        /** @var Image $photo */
        $photo = $this->Photo();
        return $photo ? '<img src="' .  $photo->ThumbnailURL(60,90) . '"/>' : '';
    }


    public function validate()
    {
        $result = parent::validate();

        if (!$this->HomeTeam()) {
            $result->addError('The home team is required');
        }

        if (!$this->AwayTeam()) {
            $result->addError('The away team is required');
        }

        return $result;
    }

}
