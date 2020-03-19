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
use Suilven\CricketSite\Helper\ImportScorecardPitcherooJSONHelper;


class ImportScorecardFromPitcherooTask extends BuildTask
{

    protected $title = 'Import a Scorecard from pitcheroo';

    protected $description = 'Create an importable spreadsheet from a Pitcheroo scorecard';

    private static $segment = 'import-pitcheroo-scorecard';

    protected $enabled = true;


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $helper = new ImportScorecardPitcherooJSONHelper();
        $url = $_GET['u'];
        $t=1;
        $ot=1;
        $competitionName = 'UNKNOWN';

        if (isset($_GET['team'])) {
            $t = $_GET['team'];
        }

        if (isset($_GET['oteam'])) {
            $ot = $_GET['oteam'];
        }

        if (isset($_GET['c'])) {
            $competitionName = $_GET['c'];
        }


        error_log('T=' . $t);
        error_log('OT=' . $ot);


        $auccTeamNumber = $this->teamXI($t);
        $otherTeamNumber = $this->teamXI($ot);


        error_log('++++ Parsing ' . $url);
        error_log('AUCC TEAM: ' . $auccTeamNumber);
        error_log('OTHER TEAM: ' . $otherTeamNumber);

        $helper->importScorecardFromURL($url, $t, $competitionName, $auccTeamNumber, $otherTeamNumber);
    }

    private function teamXI($teamNumber)
    {
        $result = 'unknown';
        switch($teamNumber)
        {
            case 1:
                $result = '1st XI';
                break;
            case 2:
                $result = '2nd XI';
                break;
            case 3:
                $result ='3rd XI';
                break;
        }

        return $result;
    }

}
