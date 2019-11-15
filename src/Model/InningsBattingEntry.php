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
use SilverStripe\ORM\DataObject;
use SilverStripe\View\HTML;

class InningsBattingEntry extends DataObject
{
    private static $table_name = 'CricketInningsBattingEntry';

    private static $db = [
        'Runs' => 'Int',

        // if this is null, balls faced was not recorded
        'BallsFaced' => 'Int',
        'Minutes' => 'Int',
        'Fours' => 'Int',
        'Sixes' => 'Int',
        'TeamScore' => 'Int',

        // sort order
        'SortOrder' => 'Int'
    ];

    private static $has_one = [
        'Innings' => Innings::class,
        'HowOut' => HowOut::class,
        'Batsman' => Player::class,

        'FieldingPlayer1' => Player::class,
        'FieldingPlayer2' => Player::class
    ];

    private static $summary_fields = [
        'Batsman.Thumbnail' => 'Image',
        'Batsman.DisplayName' => 'Batsman',
        'HowOut.ShortTitle' => 'How Out',
        'FieldingPlayer1.DisplayName' => 'Fielder',
        'FieldingPlayer2.DisplayName' =>'Bowler',
        'Runs' => 'Runs',
    ];

    /**
     * This is shown as the summary of a complex object being edited in the CMS
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->Batsman()->DisplayName;
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $teamBatting = $this->Innings()->Team();

        // this did not work until IDs used
        if ($teamBatting->ID === $this->Innings()->Match()->HomeTeam()->ID) {
            $battingPlayers = $this->Innings()->Match()->HomeTeamPlayers()->sort('Surname,FirstName');
            $fieldingPlayers = $this->Innings()->Match()->AwayTeamPlayers()->sort('Surname,FirstName');
        } else {
            $battingPlayers = $this->Innings()->Match()->AwayTeamPlayers()->sort('Surname,FirstName');
            $fieldingPlayers = $this->Innings()->Match()->HomeTeamPlayers()->sort('Surname,FirstName');
        }

        $battingPlayersMapping = $battingPlayers->map('ID', 'ReverseName');
        $fieldingPlayersMapping = $fieldingPlayers->map('ID', 'ReverseName');

        $batsmanField = DropdownField::create('BatsmanID', 'Batsman', $battingPlayersMapping)->
        setEmptyString('-- Select batsman --');
        $fields->addFieldToTab('Root.Main', $batsmanField);


        $howOuts = HowOut::get()->sort('Title')->map('ID', 'Title');
        $howOutField = DropDownField::create('HowOutID', 'How Out', $howOuts)->
            setEmptyString('-- Select mode of dismissal --');
        $fields->addFieldToTab('Root.Main', $howOutField);

        $fieldingPlayers1Field = DropdownField::create('FieldingPlayer1ID', 'Player 1', $fieldingPlayersMapping)->
        setEmptyString('-- Select fielder --');
        $fields->addFieldToTab('Root.Main', $fieldingPlayers1Field);

        $fieldingPlayers2Field = DropdownField::create('FieldingPlayer2ID', 'Player 2', $fieldingPlayersMapping)->
        setEmptyString('-- Select fielder --');
        $fields->addFieldToTab('Root.Main', $fieldingPlayers2Field);

        $runsField = new NumericField('Runs', 'Runs');
        $fields->addFieldToTab('Root.Main', $runsField);

        $ballsFacedField = new NumericField('BallsFaced', 'Balls Faced');
        $fields->addFieldToTab('Root.Main', $ballsFacedField);

        $minutesField = new NumericField('Minutes', 'Minutes');
        $fields->addFieldToTab('Root.Main', $minutesField);

        $foursField = new NumericField('Fours', 'Fours');
        $fields->addFieldToTab('Root.Main', $foursField);

        $sixesField = new NumericField('Sixes', 'Sixes');
        $fields->addFieldToTab('Root.Main', $sixesField);

        $fowField = new NumericField('TeamScore', 'Fall of Wicket');
        $fields->addFieldToTab('Root.Main', $fowField);

        // this should be done by the GridFieldSortable widget
        $fields->removeByName('SortOrder');

        return $fields;
    }


    public function getStrikeRate() {
        $sr = $this->BallsFaced == 0 ? '-' : 100*$this->Runs / $this->BallsFaced;
        return number_format((float)$sr, 2, '.', '');

    }


    public function getHowOutDescription() {
        $ho = $this->HowOut();
        $result = '';

        switch ($ho->Title) {
            case 'Bowled':
                $result = $ho->ShortTitle . '. ' . $this->FieldingPlayer2->DisplayName;
                break;
            case 'Leg Before Wicket':
                $result = $ho->ShortTitle . '. ' . $this->FieldingPlayer2->DisplayName;
                break;
            case 'Caught':
                $result = $ho->ShortTitle . '. ' . $this->FieldingPlayer1->DisplayName;
                $result .= ', b. ' . $this->FieldingPlayer2->DisplayName;
                break;
            case 'Stumped':
                $result = $ho->ShortTitle . '. ' . $this->FieldingPlayer1->DisplayName;
                $result .= ', b. ' . $this->FieldingPlayer2->DisplayName;
                break;
            default:
                $result = $ho->ShortTitle;
                break;
        }

        return $result;
    }

}
