<?php
namespace Suilven\CricketSite\Model;

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
