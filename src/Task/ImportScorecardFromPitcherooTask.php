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
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Suilven\CricketSite\Helper\ImportScorecardHelper;
use Suilven\CricketSite\Helper\ImportScorecardPitcherooHelper;


class ImportScorecardFromPitcherooTask extends BuildTask
{

    protected $title = 'Import a Scorecard from pitcheroo';

    protected $description = 'Create an importable spreadsheet from a Pitcheroo scorecard';

    private static $segment = 'import-scorecard-pitcheroo';

    protected $enabled = true;


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $helper = new ImportScorecardPitcherooHelper();
        $url = $_GET['u'];
        //error_log('Parsing ' . $url);
        $helper->importScorecardFromURL($url);


    }

}
