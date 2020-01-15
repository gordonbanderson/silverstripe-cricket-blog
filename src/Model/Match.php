<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;
use TitleDK\Calendar\Events\Event;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

class Match extends Event
{
    private static $table_name = 'CricketMatch';

    private static $db = [
        'Result' => 'Text',
        'When' => 'Datetime',
        'Status' => "Enum('Fixture,Live,Result,Cancelled,Abandoned','Fixture')",
        'Description' => 'Varchar(255)'
    ];

    private static $has_one = [
      'HomeTeam' => Team::class,
      'AwayTeam' => Team::class,
        'TossWonBy' => Team::class,
        'Competition' => Competition::class,
        'Ground' => Ground::class
    ];

    private static $has_many = [
        'Innings' => Innings::class
    ];

    private static $many_many = [
        'HomeTeamPlayers' => Player::class,
        'AwayTeamPlayers' => Player::class
    ];

    private static $many_many_extraFields = [
        'HomeTeamPlayers' => [
            'SortOrder' => 'Int',
        ],
        'AwayTeamPlayers' => [
            'SortOrder' => 'Int',
        ],
    ];

    private static $belongs_to = [
        'MatchReport' => MatchReport::class,
    ];

    private static $summary_fields = [
        'HomeTeam.Name' => 'Home Team',
        'AwayTeam.Name' => 'Away Team',
        'When' => 'When',
        'Status' => 'Status',
        'Created' => 'Created'
    ];


    private static $searchable_fields = [
        'Description'
    ];


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // remove scaffolded fields
        $fields->removeByName('HomeTeamPlayers');
        $fields->removeByName('AwayTeamPlayers');

        // result, when,status,ground,home team, away team, competition fixed

        $fields->addFieldToTab('Root.Main', DatetimeField::create(
           'When',
           'Date & Time of Match',
           $this->When
        ));

        $groundField = DropdownField::create('GroundID', 'Ground', Ground::get()->
            sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select ground --');
        $fields->addFieldToTab('Root.Main', $groundField);

        $homeTeamField = DropdownField::create('HomeTeamID', 'Home Team', Team::get()->
        sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select home team --');
        $fields->addFieldToTab('Root.Main', $homeTeamField);


        if ($this->HomeTeamID) {
            $confHome = GridFieldConfig_RelationEditor::create(20);
            $confHome->removeComponentsByType(GridFieldAddNewButton::class);

            // @todo This is not working
            $confHome->addComponent($sortable = new GridFieldSortableRows('SortOrder'));
            //$sortable->setCustomRelationName('HomeTeamPlayers');
            $teamGridHome = GridField::create(
                'HomeTeamPlayers',
                'Home Players',
                $this->HomeTeamPlayers(),
                $confHome
            );

            $fields->addFieldToTab('Root.HomeTeam', $teamGridHome);
        }

        $awayTeamField = DropdownField::create('AwayTeamID', 'Away Team', Team::get()->
        sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select away team --');
        $fields->addFieldToTab('Root.Main', $awayTeamField);

        if ($this->AwayTeamID) {
            $confAway = GridFieldConfig_RelationEditor::create(20);
            $confAway->removeComponentsByType(GridFieldAddNewButton::class);

            $autoCompleteComponent = $confAway->getComponentByType(GridFieldAddExistingAutocompleter::class);
            $autoCompleteComponent->setResultsFormat('$Surname, $FirstName');

            // @todo This does not work
            $confAway->addComponent($sortable = new GridFieldSortableRows('SortOrder'));
            //$sortable->setCustomRelationName('AwayTeanPlayers');
            $teamGridAway = GridField::create(
                'AwayTeamPlayers',
                'Away Players',
                $this->AwayTeamPlayers(),
                $confAway
            );

            $fields->addFieldToTab('Root.AwayTeam', $teamGridAway);
        }

        $teamsPlaying = new ArrayList([$this->HomeTeam(), $this->AwayTeam()]);
        $tossWonByField = DropdownField::create('TossWonByID', 'Toss Won By', $teamsPlaying->
        sort('Name')->
        map('ID', 'Title')) ->setEmptyString('-- Select toss won by --');
        $fields->addFieldToTab('Root.Main', $tossWonByField);


        $fields->addFieldToTab('Root.Main', TextareaField::create(
            'Result',
            'Description of the Result',
            $this->Result
        ));

        $inningsOfTheMatchCfg = GridFieldConfig_RecordEditor::create();
        $inningsOfTheMatchCfg->addComponent($sortable = new GridFieldSortableRows('SortOrder'));

        $fields->addFieldToTab('Root.Innings', GridField::create(
            'Innings',
            'Innings of the match',
            $this->Innings(),
            $inningsOfTheMatchCfg
        ));




        return $fields;
    }

    public function getTitle()
    {
        /*
        $title = $this->HomeTeam() ? $this->HomeTeam()->Name : 'n/a';
        $title .= ' v ';
        $title .= $this->AwayTeam() ? $this->AwayTeam()->Name : 'n/a';

        return $title;
        */
        return $this->Description;
    }


    public function getFirstInnings() {
        $result = $this->Innings()->limit(1,0)->first();
        return $result;
    }

    public function getSecondInnings() {
        return $this->Innings()->limit(1,1)->first();
    }

    public function validate()
    {
        $result = parent::validate();

        if (!$this->HomeTeam()) {
            $result->addError('The home team is required');
        }

        if (!$this->AwayTeam()) {
            $result->addError('The away team is required');
        }

        return $result;
    }


    public function HomeTeamPlayers()
    {
        return $this->getManyManyComponents('HomeTeamPlayers')->sort('SortOrder');
    }

    public function AwayTeamPlayers()
    {
        return $this->getManyManyComponents('AwayTeamPlayers')->sort('SortOrder');
    }


    /**
     * Return a string such as 'Arbroath United CC v Stirling CC'
     */
    public function matchHeading()
    {
        return $this->HomeTeam()->Name . ' v ' . $this->AwayTeam()->Name;
    }

    public function matchByLine()
    {
        $when = strtotime($this->When);

        return  $this->Ground()->Name . ' ' . date('d/m/Y, H:i', $when);
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite(); // TODO: Change the autogenerated stub
        $date = 'UNKNOWN ';
        if (!empty($this->When)) {
            $when = strtotime($this->When);
            $date = date('Ymd - ', $when);
        }
        $this->Description = $date . ' ' .
            $this->HomeTeam()->Name . ' v ' . $this->AwayTeam()->Name .' ,' .
            $this->Competition()->Name;
    }

}
