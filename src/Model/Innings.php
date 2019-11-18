<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

class Innings extends DataObject
{
    private static $table_name = 'CricketInnings';

    private static $db = [
        'Wides' => 'Int',
        'NoBalls' => 'Int',
        'Byes' => 'Int',
        'LegByes' => 'Int',
        'PenaltyRuns' => 'Int',
        'BattingSummary' => 'Text',
        'BowlingSummary' => 'Text',
        'TotalOvers' => 'Int',
        'TotalBalls' => 'Int',
        'TotalRuns' => 'Int',
        'TotalWickets' => 'Int',

        'SortOrder' => 'Int'
    ];

    private static $has_one = [
        // @todo this is bad naming, it is the batting team
        'Team' => Team::class,
        'Match' => Match::class
    ];

    private static $has_many = [
        'InningsBattingEntries' => InningsBattingEntry::class,
        'InningsBowlingEntries' => InningsBowlingEntry::class,
        'FOW' => FallOfWicket::class
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
        $inningsBattingGridCfg = GridFieldConfig_RecordEditor::create();
        $inningsBattingGridCfg->addComponent($sortable = new GridFieldSortableRows('SortOrder'));
        $fields->addFieldToTab('Root.Batting', GridField::create(
            'InningsBattingEntries',
            'Batting',
            $this->InningsBattingEntries()->sort('Created'),
            $inningsBattingGridCfg
        ));

        $fowGridCfg = GridFieldConfig_RecordEditor::create();
        $fields->addFieldToTab('Root.Batting', GridField::create(
            'FOW',
            'Fall of Wickets',
            $this->FOW()->sort('Runs'),
            $fowGridCfg
        ));

        $fields->removeByName('InningsBowlingEntries');
        $inningsBowlingGridCfg = GridFieldConfig_RecordEditor::create();
        $inningsBowlingGridCfg->addComponent($sortable = new GridFieldSortableRows('SortOrder'));
        $fields->addFieldToTab('Root.Bowling', GridField::create(
            'InningsBowlingEntries',
            'Bowling',
            $this->InningsBowlingEntries()->sort('Created'),
            $inningsBowlingGridCfg
        ));

        $widesField = new NumericField('Wides', 'Wides');
        $fields->addFieldToTab('Root.Main', $widesField);

        $noballsField = new NumericField('NoBalls', 'No-balls');
        $fields->addFieldToTab('Root.Main', $noballsField);

        $byesField = new NumericField('Byes', 'Byes');
        $fields->addFieldToTab('Root.Main', $byesField);

        $legByesField = new NumericField('LegByes', 'Leg Byes');
        $fields->addFieldToTab('Root.Main', $legByesField);

        $penaltyRunsField = new NumericField('PenaltyRuns', 'Penalty Runs');
        $fields->addFieldToTab('Root.Main', $penaltyRunsField);

        $totalRunsField = new NumericField('TotalRuns', 'Total Runs');
        $fields->addFieldToTab('Root.Main', $totalRunsField);

        $totalWicketsField = new NumericField('TotalWickets', 'Total Wickets');
        $fields->addFieldToTab('Root.Main', $totalWicketsField);

        $totalOversField = new NumericField('TotalOvers', 'Total Overs');
        $fields->addFieldToTab('Root.Main', $totalOversField);

        $totalBallsField = new NumericField('TotalBalls', 'Total Balls in Last Over');
        $fields->addFieldToTab('Root.Main', $totalBallsField);

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

    public function getTotalExtras() {
        return $this->Wides + $this->NoBalls + $this->Byes + $this->LegByes;
    }

    public function getExtrasDescription()
    {
        $extras = [];
        if ($this->Wides > 0) {
            $extras[] = $this->Wides . 'w';
        }
        if ($this->NoBalls > 0) {
            $extras[] = $this->NoBalls . 'n';
        }
        if ($this->Byes > 0) {
            $extras[] = $this->Byes . 'b';
        }
        if ($this->LegByes > 0) {
            $extras[] = $this->LegByes . 'lb';
        }
        return implode(', ', $extras);
    }

    public function getBowlingTeam()
    {
        $result = null;
        if ($this->Match()->HomeTeam() == $this->Team) {
            $result = $this->Match()->AwayTeam();
        } else {
            $result = $this->Match()->HomeTeam();

        }

        return $result;
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
