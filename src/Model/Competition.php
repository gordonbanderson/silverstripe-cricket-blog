<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;

class Competition extends DataObject
{
    private static $table_name = 'CricketCompetition';

    private static $db = [
        'Name' => 'Varchar(255)',
        'CompetitionType' => "Enum('League,Cup', 'League')",
        'SortOrder' => 'Int'
    ];

    private static $has_many = [
        'Matches' => Match::class
    ];

    private static $has_one = [
        'Season' => Season::class
    ];

    private static $belongs_many_many = [
       'Teams' => Team::class,
    ];

    private static $summary_fields = array(
        'Name'
    );

    public function getCMSFields()
    {
        /** @var FieldList $fields */
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Teams', CheckboxSetField::create(
            'Teams',
            'Teams for ' . $this->Name,
            Team::get()->map('ID','Name')
        ));

        /** @var TabSet $rootTab */
        $rootTab = $fields->first();

        /** @var Tab $mainTab */
        $mainTab = $rootTab->fieldByName('Main');

        /** @var FieldList $mainTabFields */
        $mainTabFields = $mainTab->FieldList();

        // move slug to after the name, and set it to read only
        /** @var FormField $field */
        $field = $mainTabFields->fieldByName('Slug');
        $field->setReadonly(true);
        $mainTabFields->removeByName('Slug');
        $mainTabFields->insertAfter('Name', $field);

        // hide sort order
        $mainTabFields->removeByName('SortOrder');

        return $fields;
    }


    public function getTitle()
    {
        return $this->Name;
    }

}
