<?php
namespace Suilven\CricketSite\SiteConfig;

use MadeHQ\Cloudinary\Model\ImageLink;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use Suilven\CricketSite\Model\Club;

class CricketSiteConfig extends Extension {
    private static $has_one = [
      'EmptyPlayerImage' => Image::class,
        'ClubSiteIsFor' => Club::class
    ];

    public function updateCMSFields(FieldList $fields) {
        $photoField = UploadField::create('EmptyPlayerImage', 'Image to show when no player image has been uploaded');
        $photoField->setFolderName('player-profile-images-empty');
        $photoField->setAllowedExtensions(['png', 'jpg', 'jpeg']);
        $fields->addFieldToTab('Root.Cricket', $photoField);

        $fields->addFieldToTab('Root.Cricket', DropdownField::create(
            'ClubSiteIsForID',
            'CLub for this website',
            Club::get()->map('ID','Name')
        )->setEmptyString('-- Please select club --'));
    }
}
