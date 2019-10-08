<?php
namespace Suilven\CricketSite\SiteConfig;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;

class CricketSiteConfig extends Extension {
    private static $has_one = [
      'EmptyPlayerImage' => Image::class
    ];

    public function updateCMSFields(FieldList $fields) {
        $photoField = UploadField::create('EmptyPlayerImage', 'Image to show when no player image has been uploaded');
        $photoField->setFolderName('player-profile-images-empty');
        $photoField->setAllowedExtensions(['png', 'jpg', 'jpeg']);
        $fields->addFieldToTab('Root.Cricket', $photoField);
    }
}
