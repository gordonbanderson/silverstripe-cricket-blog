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
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Suilven\CricketSite\Model\Match;
use Suilven\CricketSite\Model\TeamSheetHelper;


class CreateTeamSheetTask extends BuildTask
{

    protected $title = 'Create a Team Sheet';

    protected $description = 'Create a team sheet';

    private static $segment = 'create-team-sheet';

    protected $enabled = true;


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $matchID = $_GET['id'];
        $match = DataObject::get_by_id(Match::class, $matchID);
        if (!$match) {
            user_error('Match with id ' . $matchID . ' not found');
        }

        $helper = new TeamSheetHelper();
        $helper->makeTeamSheet($match);

    }








}
