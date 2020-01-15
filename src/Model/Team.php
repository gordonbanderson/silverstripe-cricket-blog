<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
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

    private static $summary_fields = [
        'Name' => 'Name',
        'Slug' => 'Slug'
    ];


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
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
        return $fields;
    }


    public function validate() {
        $result = parent::validate();

        if(Team::get()->filter(['Name' => $this->Name])->count() > 1) {
            $result->addError('Team Name Must Be Unique');
        }

        return $result;
    }
}
