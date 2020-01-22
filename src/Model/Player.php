<?php
namespace Suilven\CricketSite\Model;

use Level51\Cloudinary\Image;
use Level51\Cloudinary\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Parsers\URLSegmentFilter;

class Player extends DataObject
{
    private static $table_name = 'CricketPlayer';

    private static $db = [
        'FirstName' => 'Varchar(255)',
        'Surname' => 'Varchar(255)',
        'DisplayName' => 'Varchar(255)',
        'Slug' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Photo' => Image::class,
    ];

    private static $belongs_many_many = [
        'Clubs' => Club::class,
        'Matches' => Match::class
    ];

    private static $has_many = [
      'Innings' => InningsBattingEntry::class
    ];


    private static $summary_fields = [
        'Thumbnail' => 'Image',
        'FirstName' => 'FirstName',
        'Surname' => 'Surname',
        'Name' => 'DisplayName',
        'Slug' => 'Slug'
    ];

    private static $default_sort = '"Surname", "FirstName"';

    private static $searchable_fields = array(
        'FirstName',
        'Surname',
        'DisplayName',
    );


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('PhotoID');

        $fields->addFieldToTab('Root.Clubs', GridField::create(
            'Clubs',
            'Clubs for ' . $this->DisplayName,
            $this->Clubs(),
            GridFieldConfig_RecordEditor::create()
        ));

        $photoField = UploadField::create('Photo');
        $photoField->setFolderName('player-profile-images');
      //  $photoField->setAllowedExtensions(['png', 'jpg', 'jpeg']);
        $fields->addFieldToTab('Root.Main', $photoField);

        /** @var TabSet $rootTab */
        $rootTab = $fields->first();

        /** @var Tab $mainTab */
        $mainTab = $rootTab->fieldByName('Main');

        /** @var FieldList $mainTabFields */
        $mainTabFields = $mainTab->FieldList();


        // move slug to after the name, and set it to read only
        /** @var FormField $field */
        $field = $mainTabFields->fieldByName('Slug');
        $field->setReadonly(true);
        $mainTabFields->removeByName('Slug');
        $mainTabFields->insertAfter('Name', $field);

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

    public function getThumbnail()
    {
        // balancing act on size here, too large and the rows are spread out for the other info
        return  $this->getPlayerImage()->Fill(28,28);
    }

    public function getPlayerImage()
    {
        $config = SiteConfig::current_site_config();
        $emtpyPlayerImage = $config->EmptyPlayerImage();
        return $this->Photo()->exists() ? $this->Photo() : $emtpyPlayerImage;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite(); // TODO: Change the autogenerated stub
        $urlFilter = new URLSegmentFilter();
        $slug = $urlFilter->filter($this->DisplayName);
        $this->Slug = $slug;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords(); // TODO: Change the autogenerated stub

        $playersWithNoSlug = Player::get()->filter(['Slug' => null]);
        foreach($playersWithNoSlug as $player) {
            $player->write();
        }
    }

    public function Link()
    {
        return '/players/show/' . $this->Slug;
    }

}
