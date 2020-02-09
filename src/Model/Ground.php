<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataObject;
use Smindel\GIS\Forms\MapField;
use Smindel\GIS\GIS;

class Ground extends DataObject
{
    private static $table_name = 'CricketGround';

    private static $db = [
        'Name' => 'Varchar(255)',
        'Location' => 'Geometry',
    ];

    private static $belongs_many_many = [
       'Clubs' => Club::class,
    ];

    private static $summary_fields = [
        'Name'
    ];

    private static $default_sort = [
        'Name'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Clubs', GridField::create(
            'Clubs',
            'Clubs for ' . $this->Name,
            $this->Clubs(),
            GridFieldConfig_RecordEditor::create()
        ));

        $fields->addFieldToTab(
            'Root.Main',
            MapField::create('Location')
                ->setControl('polyline', false)
                ->setControl('polygon', false)
                ->enableMulti(true),
            'Content'
        );


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

    public function getTitle()
    {
        return $this->Name;
    }

    public function getCoordinatesAsJSON()
    {
        $location = $this->Location;
        $coordinates = GIS::create($location)->coordinates;
        return json_encode($coordinates);
    }

    public function HasGeo()
    {
        return !is_null($this->Location);
    }

    public function getLatitude()
    {
        $location = $this->Location;
        $coordinates = GIS::create($location)->coordinates;
        return $coordinates[0];
    }

    public function getLongitude()
    {
        $location = $this->Location;
        $coordinates = GIS::create($location)->coordinates;
        return $coordinates[1];
    }

    public function Link()
    {
        return '/grounds/forecast/'.$this->Slug;
    }

    public function LinkingMode()
    {
        return Controller::curr()->getRequest()->param('ID') == $this->Slug ? 'current' : 'link';
    }

    public function PrevGround()
    {
        // @todo Make this more efficient
        $grounds = Ground::get()->sort('Name DESC')->filter('Location:not', null);
        $nextGround = null;
        $foundCurrent = false;
        foreach($grounds as $ground)
        {

            if ($foundCurrent == true) {
                $foundCurrent = false;
                $nextGround = $ground;
                break;
            }

            if ($ground->ID == $this->ID) {
                $foundCurrent = true;
            }

        }

        return $nextGround;

    }

    public function NextGround()
    {
        // @todo Make this more efficient
        $grounds = Ground::get()->sort('Name')->filter('Location:not', null);
        $nextGround = null;
        $foundCurrent = false;
        foreach($grounds as $ground)
        {

            if ($foundCurrent == true) {
                $foundCurrent = false;
                $nextGround = $ground;
                break;
            }

            if ($ground->ID == $this->ID) {
                $foundCurrent = true;
            }

        }

        return $nextGround;

    }


}
