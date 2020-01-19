<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;

class Season extends DataObject
{
    private static $table_name = 'CricketSeason';

    private static $db = [
      'Name' => 'Varchar',
        'StartDate' => 'Date',
        'FinishDate' => 'Date'
    ];

    private static $has_many = [
        'Competitions' => Competition::class
    ];

    private static $default_sort = [
        'Name DESC'
    ];
}
