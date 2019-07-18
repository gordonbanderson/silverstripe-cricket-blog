<?php

class CricketAdmin extends \SilverStripe\Admin\ModelAdmin
{
    private static $managed_models = [
        \Suilven\CricketSite\Model\Club::class,
       // \Suilven\CricketSite\Model\Player::class
    ];

    private static $url_segment = 'cricket';

    private static $menu_title = 'Cricket';
}
