<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class InningsEntry extends DataObject
{
    private static $table_name = 'CricketInningsEntry';

    private static $db = [
       'Runs' => 'Int'
    ];

    private static $has_one = [
        'Innings' => Innings::class,
        'HowOut' => HowOut::class
    ];

    public function getCMSFieldsNOT()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Clubs', GridField::create(
            'Clubs',
            'Clubs for ' . $this->Name,
            $this->Clubs(),
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
