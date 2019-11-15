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

class InningsBowlingEntry extends DataObject
{
    private static $table_name = 'CricketInningsBowlingEntry';

    private static $db = [
        'Overs' => 'Int',
        'Balls' => 'Int',
        'Maidens' => 'Int',
        'Runs' => 'Int',
        'Wickets' => 'Int',
        'Wides' => 'Int',
        'NoBalls' => 'Int',
        'Fours' => 'Int',
        'Sixes' => 'Int',

        // sort order
        'SortOrder' => 'Int'
    ];

    private static $has_one = [
        'Innings' => Innings::class,
        'Bowler' => Player::class,
    ];

    private static $summary_fields = [
        'Bowler.Thumbnail' => 'Bowler',
        'Bowler.DisplayName' => 'Bowler',
        'Overs' => 'O',
        'Maidens' => 'M',
        'Runs' => 'Runs',
        'Wickets' => 'Wickets',
    ];

    /**
     * This is shown as the summary of a complex object being edited in the CMS
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->Bowler()->DisplayName;
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $teamBowling = $this->Innings()->Team();

        if ($teamBowling->ID === $this->Innings()->Match()->HomeTeam()->ID) {
            $bowlingPlayers = $this->Innings()->Match()->AwayTeamPlayers()->sort('Surname,FirstName');
        } else {
            $bowlingPlayers = $this->Innings()->Match()->HomeTeamPlayers()->sort('Surname,FirstName');
        }

        $bowlingPlayersMapping = $bowlingPlayers->map('ID', 'ReverseName');

        $bowlerField = DropdownField::create('BowlerID', 'Bowler', $bowlingPlayersMapping)->
        setEmptyString('-- Select bowler --');
        $fields->addFieldToTab('Root.Main', $bowlerField);

        $oversField = new NumericField('Overs', 'Overs');
        $fields->addFieldToTab('Root.Main', $oversField);

        $ballsField = new NumericField('Balls', 'Balls');
        $fields->addFieldToTab('Root.Main', $ballsField);

        $maidensField = new NumericField('Maidens', 'Maidens');
        $fields->addFieldToTab('Root.Main', $maidensField);

        $runsField = new NumericField('Runs', 'Runs');
        $fields->addFieldToTab('Root.Main', $runsField);

        $wicketsField = new NumericField('Wickets', 'Wickets');
        $fields->addFieldToTab('Root.Main', $wicketsField);

        $foursField = new NumericField('Fours', 'Fours');
        $fields->addFieldToTab('Root.Main', $foursField);

        $sixesField = new NumericField('Sixes', 'Sixes');
        $fields->addFieldToTab('Root.Main', $sixesField);

        $widesField = new NumericField('Wides', 'Wides');
        $fields->addFieldToTab('Root.Main', $widesField);

        $noBallsField = new NumericField('NoBalls', 'No Balls');
        $fields->addFieldToTab('Root.Main', $noBallsField);


        /*

        // this did not work until IDs used


        $battingPlayersMapping = $battingPlayers->map('ID', 'ReverseName');



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
*/


        return $fields;
    }


    public function getEconomyRate()
    {
        $er = 6*$this->Runs / (6 * $this->Overs + $this->Balls);
        return number_format((float)$er, 2, '.', '');

    }
}
