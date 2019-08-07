<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class InningsEntry extends DataObject
{
    private static $table_name = 'CricketInningsEntry';

    private static $db = [
        'Runs' => 'Int',

        // if this is null, balls faced was not recorded
        'BallsFaced' => 'Int'
    ];

    private static $has_one = [
        'Innings' => Innings::class,
        'HowOut' => HowOut::class,
        'Batsman' => Player::class,

        'FieldingPlayer1' => Player::class,
        'FieldingPlayer2' => Player::class
    ];


    public function getTitle()
    {
        return $this->Batsman->DisplayName;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $teamBatting = $this->Innings()->Team();
        $players = $teamBatting->Club()->Players()->sort('Surname,FirstName');

        $playersDropdown = $players->map('ID', 'ReverseName');

        $playersField = DropdownField::create('BatsmanID', 'Batsman', $playersDropdown)->
        setEmptyString('-- Select batsman --');
        $fields->addFieldToTab('Root.Main', $playersField);

        $howOuts = HowOut::get()->sort('Title')->map('ID', 'Title');
        $howOutField = DropDownField::create('HowOutID', 'How Out', $howOuts)->
            setEmptyString('-- batting --');
        $fields->addFieldToTab('Root.Main', $howOutField);

        $runsField = new NumericField('Runs', 'Runs');
        $fields->addFieldToTab('Root.Main', $runsField);



        return $fields;
    }


    // cannot get this to work for some reason, the trait for image tweaking is missing and the HTML needs to be converted
    // and not returned raw
    public function getPhotoThumbnail() {
        // display a thumbnail of the Image from the has_one relation

        /** @var Image $photo */
        $photo = $this->Photo();
        return $photo ? '<img src="' .  $photo->ThumbnailURL(60,90) . '"/>' : '';
    }




}
