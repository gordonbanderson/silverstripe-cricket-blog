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


class ImportScorecardTask extends BuildTask
{

    protected $title = 'Import a Scorecard';

    protected $description = 'Import a scorecard from a standard spreadsheet';

    private static $segment = 'import-scorecard';

    protected $enabled = true;


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $spreadsheet = $_GET['path'];
        error_log('SS: ' . $spreadsheet);

        if (empty($spreadsheet)) {
            user_error('Please provide a spreadsheet with the path parameter');
        }

        if (!(file_exists($spreadsheet))) {
            user_error('The file ' . $spreadsheet . ' does not exist');
        }

        // we know at this point that the file exists
        $helper = new ImportScorecardHelper();
        $helper->importScorecardFromSpreadsheet($spreadsheet);
    }








}
