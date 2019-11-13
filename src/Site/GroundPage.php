<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;
use Smindel\GIS\Forms\MapField;

class GroundPage extends \Page
{
    private static $table_name = 'CricketGroundPage';

    private static $has_one = [
      'Ground' => 'Ground'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $grounds = Ground::get()->sort('Name');

        $groundsField = DropdownField::create('GroundID', 'Ground', $grounds)->
        setEmptyString('-- Select ground --');
        $fields->addFieldToTab('Root.Main', $groundsField);


        return $fields;
    }

}
