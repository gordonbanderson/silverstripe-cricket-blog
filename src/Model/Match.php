<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class Match extends DataObject
{
    private static $table_name = 'CricketMatch';

    private static $db = [
        'Result' => 'Text',
        'When' => 'Datetime',
        'Status' => "Enum('Fixture,Live,Result','Fixture')"
    ];

    private static $has_one = [
      'HomeTeam' => Team::class,
      'AwayTeam' => Team::class,
        'Competition' => Competition::class,
        'Ground' => Ground::class
    ];

    private static $has_many = [
        'Innings' => Innings::class
    ];

    private static $belongs_to = [
        MatchReport::class
    ];

    private static $summary_fields = [
      'HomeTeam.Name' => 'Home Team',
        'AwayTeam.Name' => 'Away Team',
        'When' => 'When',
        'Status' => 'Status'
    ];


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // result, when,status,ground,home team, away team, competition fixed

        $fields->addFieldToTab('Root.Main', DatetimeField::create(
           'When',
           'Date & Time of Match',
           $this->When
        ));

        $groundField = DropdownField::create('GroundID', 'Ground', Ground::get()->
            sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select ground --');
        $fields->addFieldToTab('Root.Main', $groundField);

        $homeTeamField = DropdownField::create('HomeTeamID', 'Home Team', Team::get()->
        sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select home team --');
        $fields->addFieldToTab('Root.Main', $homeTeamField);

        $awayTeamField = DropdownField::create('AwayTeamID', 'Away Team', Team::get()->
        sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select away team --');
        $fields->addFieldToTab('Root.Main', $awayTeamField);

        $fields->addFieldToTab('Root.Main', TextareaField::create(
            'Result',
            'Description of the Result',
            $this->Result
        ));

        $fields->addFieldToTab('Root.Innings', GridField::create(
            'Innings',
            'Innings of the match',
            $this->Innings(),
            GridFieldConfig_RecordEditor::create()
        ));

        return $fields;
    }

    public function getTitle()
    {
        $title = $this->HomeTeam() ? $this->HomeTeam()->Name : 'n/a';
        $title .= ' v ';
        $title .= $this->AwayTeam() ? $this->AwayTeam()->Name : 'n/a';

        return $title;
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