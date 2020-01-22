<?php
namespace Suilven\CricketSite\Model;

use SilverStripe\Forms\DropdownField;

class GroundPage extends \Page
{
    private static $table_name = 'CricketGroundPage';

    private static $has_one = [
      'Ground' => 'Ground'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $grounds = Ground::get()->sort('Name');

        $groundsField = DropdownField::create('GroundID', 'Ground', $grounds)->
        setEmptyString('-- Select ground --');
        $fields->addFieldToTab('Root.Main', $groundsField);


        return $fields;
    }

}
