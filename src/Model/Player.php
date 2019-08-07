<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class Player extends DataObject
{
    private static $table_name = 'CricketPlayer';

    private static $db = [
        'FirstName' => 'Varchar(255)',
        'Surname' => 'Varchar(255)',
        'DisplayName' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Photo' => Image::class,
    ];

    private static $belongs_many_many = [
       'Clubs' => Club::class,
        'Matches' => Match::class
    ];

    private static $has_many = [
      'Innings' => InningsEntry::class
    ];

    private static $summary_fields = array(
        'DisplayName'
    );

    private static $default_sort = '"Surname", "FirstName"';

    private static $searchable_fields = array(
        'FirstName',
        'Surname',
        'DisplayName',
    );


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Clubs', GridField::create(
            'Clubs',
            'Clubs for ' . $this->DisplayName,
            $this->Clubs(),
            GridFieldConfig_RecordEditor::create()
        ));

        $photoField = UploadField::create('Photo');
        $photoField->setFolderName('player-profile-images');
        $photoField->setAllowedExtensions(['png', 'jpg', 'jpeg']);
        $fields->addFieldToTab('Root.Main', $photoField);

        return $fields;
    }

    public function getReverseName()
    {
        return $this->Surname . ',' . $this->FirstName;
    }

    public function getTitle()
    {
        return $this->DisplayName;
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
