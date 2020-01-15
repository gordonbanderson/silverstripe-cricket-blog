<?php

class CricketAdmin extends \SilverStripe\Admin\ModelAdmin
{
    private static $managed_models = [
        \Suilven\CricketSite\Model\Club::class,
        \Suilven\CricketSite\Model\Competition::class,
        \Suilven\CricketSite\Model\Ground::class
    ];

    private static $url_segment = 'cricket';

    private static $menu_title = 'Cricket';

    // Icon sourced from http://www.myiconfinder.com/icon/cricket-ball-sport-bat-game-play/17550#.24,
    // by Ivan Boyko, used under the terms of the Creative Commons (Attributions 3.0 unported)
    private static $menu_icon = "suilven/cricket-blog:client/dist/icons/cricket.png";
}
