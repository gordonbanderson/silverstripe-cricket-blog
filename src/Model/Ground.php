<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;
use Smindel\GIS\Forms\MapField;

class Ground extends DataObject
{
    private static $table_name = 'CricketGround';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Location' => 'Geometry',
    ];

    private static $belongs_many_many = [
       'Clubs' => Club::class,
    ];

    private static $summary_fields = array(
        'Name'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Clubs', GridField::create(
            'Clubs',
            'Clubs for ' . $this->Name,
            $this->Clubs(),
            GridFieldConfig_RecordEditor::create()
        ));

        $fields->addFieldToTab(
            'Root.Main',
            MapField::create('Location')
                ->setControl('polyline', false)
                ->setControl('polygon', true)
                ->enableMulti(true),
            'Content'
        );

        return $fields;
    }

    public function getTitle()
    {
        return $this->Name;
    }

}
