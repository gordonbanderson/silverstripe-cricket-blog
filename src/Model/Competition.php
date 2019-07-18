<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class Competition extends DataObject
{
    private static $table_name = 'CricketCompetition';

    private static $db = [
        'Name' => 'Varchar(255)',
        'CompetitionType' => "Enum('League,Cup', 'League')"
    ];

    private static $belongs_many_many = [
       'Teams' => Team::class,
    ];

    private static $summary_fields = array(
        'Name'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Teams', CheckboxSetField::create(
            'Teams',
            'Teams for ' . $this->Name,
            Team::get()->map('ID','Name')
        ));

        return $fields;
    }

    public function getTitle()
    {
        return $this->Name;
    }

}
