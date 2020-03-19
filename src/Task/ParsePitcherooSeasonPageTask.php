<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\CricketSite\Task;

use PHPHtmlParser\Dom;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Suilven\CricketSite\Helper\ImportScorecardHelper;


class ParsePitcherooSeasonPageTask extends BuildTask
{

    protected $title = 'Parse Pitcheroo Season Page';

    protected $description = 'Parse a Pitcheroo Season Page for Scorecard Links';

    private static $segment = 'parse-pitcheroo-season-page';

    protected $enabled = true;


    public function run($request)
    {
        // check this script is being run by admin
        $canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
        if (!$canAccess) {
            return Security::permissionFailure($this);
        }

        $url = $_GET['u'];
        error_log('Parsing ' . $url);
        $dom = new Dom();

        $dom->loadFromUrl($url, [
            'removeStyles' => true,
            'cleanupInput' => false,
            'preserveLineBreaks' => true
        ]);

        $links = $dom->find('a');

        /** @var \DOMElement $link */
        foreach($links as $link) {
            error_log($link->getAttribute('href'));
        }
    }

}
