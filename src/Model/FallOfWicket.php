<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObject;

class FallOfWicket extends DataObject
{
    private static $table_name = 'CricketFallOfWicket';

    private static $db = [
        'Runs' => 'Int',
    ];

    private static $has_one = [
        'Batsman' => Player::class,
        'Innings' => Innings::class,
    ];

    private static $summary_fields = [
        'Batsman.Thumbnail' => 'Image',
        'Batsman.DisplayName' => 'Batsman',
        'Runs' => 'Runs',
    ];

    /**
     * This is shown as the summary of a complex object being edited in the CMS
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->Runs . ' - ' . $this->Batsman()->DisplayName;
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $teamBatting = $this->Innings()->Team();

        // this did not work until IDs used
        if ($teamBatting->ID === $this->Innings()->Match()->HomeTeam()->ID) {
            $battingPlayers = $this->Innings()->Match()->HomeTeamPlayers()->sort('Surname,FirstName');
        } else {
            $battingPlayers = $this->Innings()->Match()->AwayTeamPlayers()->sort('Surname,FirstName');
        }

        $battingPlayersMapping = $battingPlayers->map('ID', 'ReverseName');

        $batsmanField = DropdownField::create('BatsmanID', 'Batsman', $battingPlayersMapping)->
        setEmptyString('-- Select batsman --');
        $fields->addFieldToTab('Root.Main', $batsmanField);

        $runsField = new NumericField('Runs', 'Score at Fall Of Wicket');
        $fields->addFieldToTab('Root.Main', $runsField);

        return $fields;
    }


}
