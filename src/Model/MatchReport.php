<?php


namespace Suilven\CricketSite\Model;


use SilverStripe\Blog\Model\BlogPost;

class MatchReport extends BlogPost
{
    private static $table_name = 'CricketMatchReport';

    private static $has_one = [
        'Match' => Match::class
    ];

}
