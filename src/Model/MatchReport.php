<?php


namespace Suilven\CricketSite\Model;


use SilverShop\HasOneField\HasOneButtonField;
use SilverStripe\Blog\Model\BlogPost;

class MatchReport extends BlogPost
{
    private static $table_name = 'CricketMatchReport';

    private static $has_one = [
        'Match' => Match::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main",
            HasOneButtonField::create($this, "Match"),
            'Content'
        );

        return $fields;
    }

}
