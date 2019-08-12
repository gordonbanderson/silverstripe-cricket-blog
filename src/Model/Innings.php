<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class Innings extends DataObject
{
    private static $table_name = 'CricketInnings';

    private static $db = [
        'Wides' => 'Int',
        'NoBalls' => 'Int',
        'Byes' => 'Int',
        'LegByes' => 'Int',
        'BattingSummary' => 'Text',
        'BowlingSummary' => 'Text',
        'TotalRuns' => 'Int',
        'TotalWickets' => 'Int'
    ];

    private static $has_one = [
        'Team' => Team::class,
        'Match' => Match::class
    ];

    private static $has_many = [
        'InningsBattingEntries' => InningsBattingEntry::class,
        'InningsBowlingEntries' => InningsBowlingEntry::class,
    ];

    private static $belongs_to = [
        Match::class
    ];

    private static $summary_fields = [
        'Team.Name' => 'Innings Of',
        'BattingSummary' => 'Batting',
        'BowlingSummary' => 'Bowling'
    ];


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $homeTeam = $this->Match()->HomeTeam();
        $awayTeam = $this->Match()->AwayTeam();
        $teams = [
            $homeTeam->ID => $homeTeam->Name,
            $awayTeam->ID => $awayTeam->Name
        ];

        $teamField = DropdownField::create('TeamID', 'Batting Team', $teams)->
            setEmptyString('-- Select batting team --');
        $fields->addFieldToTab('Root.Main', $teamField);

        $fields->removeByName('InningsBattingEntries');
        $fields->addFieldToTab('Root.Batting', GridField::create(
            'InningsBattingEntries',
            'Batting',
            $this->InningsBattingEntries()->sort('Created'),
            GridFieldConfig_RecordEditor::create()
        ));

        $fields->removeByName('InningsBowlingEntries');
        $fields->addFieldToTab('Root.Bowling', GridField::create(
            'InningsBowlingEntries',
            'Bowling',
            $this->InningsBowlingEntries()->sort('Created'),
            GridFieldConfig_RecordEditor::create()
        ));

        $widesField = new NumericField('Wides', 'Wides');
        $fields->addFieldToTab('Root.Main', $widesField);

        $noballsField = new NumericField('NoBalls', 'No-balls');
        $fields->addFieldToTab('Root.Main', $noballsField);

        $byesField = new NumericField('Byes', 'Byes');
        $fields->addFieldToTab('Root.Main', $byesField);

        $legByesField = new NumericField('LegByes', 'Leg Byes');
        $fields->addFieldToTab('Root.Main', $legByesField);


        $totalRunsField = new NumericField('TotalRuns', 'Total Runs');
        $fields->addFieldToTab('Root.Main', $totalRunsField);

        $totalWicketsField = new NumericField('TotalWickets', 'Total Wickets');
        $fields->addFieldToTab('Root.Main', $totalWicketsField);

        $battingSummaryField = new TextareaField('BattingSummary', 'Batting Summary');
        $fields->addFieldToTab('Root.Main', $battingSummaryField);

        $bowlingSummaryField = new TextareaField('BowlingSummary', 'Bowling Summary');
        $fields->addFieldToTab('Root.Main', $bowlingSummaryField);

        return $fields;
    }

    public function getTitle()
    {
        return 'Team: ' . $this->Team()->Name;
    }



    public function validate()
    {
        $result = parent::validate();

        if (!$this->Match()->HomeTeam()) {
            $result->addError('The home team is required');
        }

        if (!$this->Match()->AwayTeam()) {
            $result->addError('The away team is required');
        }

        return $result;
    }

}
