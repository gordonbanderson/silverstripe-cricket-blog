<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class HowOut extends DataObject
{
    private static $table_name = 'CricketHowOut';

    private static $db = [
        'Title' => 'Varchar',
        'ShortTitle'=> 'Varchar',
        'Player1Needed' => 'Boolean',
        'Player2Needed' => 'Boolean',
        'Player3Needed' => 'Boolean',
    ];



    private static $has_many = [
        InningsBattingEntry::class
    ];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $nExisting = HowOut::get()->count();
        if ($nExisting == 0) {
            HowOut::create([
                    'Title' => 'Bowled',
                    'ShortTitle' => 'b',
                    'Player2Needed' => true
                ]
            )->write();


            HowOut::create([
                    'Title' => 'Caught',
                    'ShortTitle' => 'c',
                    'Player1Needed' => true,
                    'Player2Needed' => true
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Stumped',
                    'ShortTitle' => 'st',
                    'Player1Needed' => true,
                    'Player2Needed' => true
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Caught and Bowled',
                    'ShortTitle' => 'c&b',
                    'Player2Needed' => true
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Leg Before Wicket',
                    'ShortTitle' => 'lbw',
                    'Player2Needed' => true
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Retired',
                    'ShortTitle' => 'retired'
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Retired Hurt',
                    'ShortTitle' => 'retired hurt'
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Hit The Ball Twice',
                    'ShortTitle' => 'hit the ball twice'
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Hit Wicket',
                    'ShortTitle' => 'hit wicket'
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Did Not Bat',
                    'ShortTitle' => 'did not bat',
                    'Player2Needed' => true
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Obstructing the Field',
                    'ShortTitle' => 'obstructing the field'
                ]
            )->write();


            HowOut::create([
                    'Title' => 'Timed Out',
                    'ShortTitle' => 'timed out'
                ]
            )->write();

            HowOut::create([
                    'Title' => 'Not Out',
                    'ShortTitle' => 'not out'
                ]
            )->write();
        }



    }


}
