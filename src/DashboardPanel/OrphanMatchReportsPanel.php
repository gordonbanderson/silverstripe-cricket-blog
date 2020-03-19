<?php
namespace Suilven\CricketSite\DashboardPanel;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Suilven\CricketSite\Model\MatchReport;
use UncleCheese\Dashboard\DashboardPanel;

class OrphanMatchReportsPanel extends DashboardPanel {

    private static $db = [
        'Amount' => 'Int'
    ];

    private static $defaults = [
        'Amount' => 10
    ];

    public function getLabel() {
        return 'Match Reports With No Match Data';
    }


    public function getDescription() {
        return 'Shows match reports missing match data';
    }


    public function getConfiguration() {
        $fields = parent::getConfiguration();
        $fields->push(TextField::create("Amount", "Number of match reports to show"));
        return $fields;
    }



    public function MatchReports() {
        return MatchReport::get()->where(['"CricketMatchReport"."MatchID"' => 0])->limit($this->Amount);
    }
}
