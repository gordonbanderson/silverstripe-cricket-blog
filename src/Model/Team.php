<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\ORM\DataObject;

class Team extends DataObject
{
    private static $table_name = 'CricketTeam';

    private static $db = [
      'Name' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Club' => Club::class
    ];

    private static $many_many = [
        'Competitions' => Competition::class
    ];
}
