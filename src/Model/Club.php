<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;

class Club extends DataObject
{
    private static $table_name = 'CricketClub';

    private static $db = [
      'Name' => 'Varchar(255)'
    ];

    private static $has_many = [
      'Teams' => Team::class,
    ];

    private static $many_many = [
        'Players' => Player::class,
        'Grounds' => Ground::class
    ];

    private static $default_sort = 'Name';

    private static $summary_fields = [
        'Name' => 'Name',
        'Slug' => 'Slug'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Teams', GridField::create(
            'Teams',
            'Teams for ' . $this->Name,
            $this->Teams(),
            GridFieldConfig_RecordEditor::create()
        ));

        $conf = GridFieldConfig_RelationEditor::create(20);
        $teamGrid = GridField::create(
            'Players',
            'Players',
            $this->Players(),
            $conf
        );

        $fields->addFieldToTab('Root.Players', $teamGrid);

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

        if(Club::get()->filter(['Name' => $this->Name])->count() > 1) {
            $result->addError('Team Name Must Be Unique');
        }

        return $result;
    }
}
