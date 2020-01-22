<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\CricketSite\Task;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Suilven\CricketSite\Model\Match;
use TitleDK\Calendar\Events\Event;


class MigrateMatchToEventsTask extends BuildTask
{

    protected $title = 'Run this after Match extends Event';

    protected $description = 'Data migration of Match extends Event';

    private static $segment = 'match-extends-event';

    protected $enabled = true;


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }


        $matches = DB::query('SELECT "ID" from "CricketMatch"');
        foreach ($matches as $match)
        {
            $event = new Event();
            $event->ID = $match['ID'];
            $event->write();
        }

        DB::query('UPDATE "Event" SET "ClassName" = \'Suilven\\CricketSite\\Model\\Match\'');

        $matches = Match::get();
        foreach($matches as $match) {
            error_log($match->When);
            $match->StartDateTime = $match->When;
            $match->write();
        }
    }

}
