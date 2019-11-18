<?php

namespace Suilven\CricketSite\Helper;

use PHPHtmlParser\Dom;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpParser\Node;
use SilverStripe\ORM\DataObject;
use Suilven\CricketSite\Model\Club;
use Suilven\CricketSite\Model\Competition;
use Suilven\CricketSite\Model\FallOfWicket;
use Suilven\CricketSite\Model\Ground;
use Suilven\CricketSite\Model\HowOut;
use Suilven\CricketSite\Model\Innings;
use Suilven\CricketSite\Model\InningsBattingEntry;
use Suilven\CricketSite\Model\InningsBowlingEntry;
use Suilven\CricketSite\Model\Match;
use Suilven\CricketSite\Model\Player;
use Suilven\CricketSite\Model\Team;
use Suilven\Sluggable\Helper\SluggableHelper;

class ImportScorecardPitcherooHelper
{

    private $homeClub;
    private $awayClub;

    private $homeTeam;
    private $awayTeam;

    private $ground;
    private $match;
    private $competitionName;


    public function importScorecardFromURL($url)
    {

        $spreadsheet = new Spreadsheet();
        $this->initialiseSpreadsheet($spreadsheet);
        $this->parsePitcheroo($spreadsheet, $url);

        $writer = new Xlsx($spreadsheet);
        $writer->save('test.xls');
    }


    private function parsePitcheroo(&$spreadsheet, $url)
    {
        $dom = new Dom();
        //$dom->loadFromUrl($url);
        $dom->loadFromFile('./pitcheroo.html',[
            'removeStyles' => true,
            'cleanupInput' => false,
            'preserveLineBreaks' => true
        ]);

        /** @var Dom\HtmlNode $shield */
        $shield = $dom->find('g[@id="shield]')[0];
        $inningsScorecardHTML = $shield->getParent()->getParent()->getParent()->getParent()->getParent()->getParent();
        //$teamBatting = $inningsScorecardHTML->find('h3');

        $i = 0;


        $teamBatting = $this->getTeamBatting($inningsScorecardHTML);

        $this->beingParsingCard($inningsScorecardHTML);


        //error_log('Batting: ' . $teamBatting);


        /*
         * Notes
         * error_log($level1Divs[2]); << this has the toss winner
         */


    }


    private function beingParsingCard($inningsScorecardHTML)
    {
        $level1Divs = $inningsScorecardHTML->find('div');
        $battingCard = $level1Divs[3];
        $level2Divs = $battingCard->find('div');
        $this->parseBattingCard($level2Divs[1]);
        error_log('+++++++');
        $this->exploreNodeset($level2Divs[1]);
        die;
    }

    /**
     * The first child div can be ignored, the rest are batting details
     * @param Dom\HtmlNode $nodes Nodeset
     */
    private function parseBattingCard($nodes)
    {
        $batsmanDivs = $nodes->find('div');
        for($i = 1; $i<sizeof($nodes); $i++) {
            $batsmanNodeset = $batsmanDivs[$i];
            $level3Divs = $batsmanNodeset->find('div');
            $level4Divs = $level3Divs->find('div');

            $this->exploreNodeset($level3Divs);

            // $level4Divs[0] contains the avatar, batsman name and method of dismissal
            $avatarBlock = $level4Divs[0]->find('div')[1];
            if (is_null($avatarBlock)) {
                break;
            }
            $batsmanNameDiv = $avatarBlock->find('div')[0];

            $batsmanName = $batsmanNameDiv->innerHtml;
            error_log('BATSMAN:' . $batsmanName);
            $dismissal = $avatarBlock->find('span')[0]->innerHtml;
            error_log('DISMISSAL: ' . $dismissal);
          //  $this->exploreNodeset($avatarBlock->find('div')[0]);
         //   die;
            $runs = $level4Divs[1]->innerHtml;
            $balls = $level4Divs[2]->innerHtml;
            $fours = $level4Divs[3]->innerHtml;
            $sixes = $level4Divs[4]->innerHtml;
            error_log('RUNS: ' . $runs);
            error_log('BALLS: ' . $balls);
            error_log('FOURS: ' . $fours);
            error_log('SIXES: ' . $sixes);
            error_log('----');
        }

        error_log('I: ' . $i);
        // this contains 'Extras' with the breakdown as [0], [1] is the total of extras
        $extrasDiv = $batsmanDivs[$i]->find('div')[0];
        error_log('----------------------------------');
        $this->exploreNodeset($extrasDiv);
        $extrasBreakdown = $extrasDiv->find('div')[0]->find('span')[0]->innerHtml;

        $totalExtras = $extrasDiv->find('div')[1]->innerHtml;
        error_log('EXTRAS: ' . $totalExtras);
        error_log('EXTRAS: ' . $extrasBreakdown);
        die;
    }

    private function getTeamBatting($inningsScorecardHTML)
    {
        $divs = $inningsScorecardHTML->find('div div');
        $teamBattingDiv = $divs[0]->find('div')[1]->find('div]');
        return $teamBattingDiv[4]->find('span')[0]->innerHtml;
    }

    private function exploreNodeset($nodes)
    {
        $i = 0;
        foreach($nodes as $node) {
            error_log('---- ' . $i . ' ----');
            error_log($node);
            $i++;
            error_log("\n\n\n\n\n\n\n\n\n");
        }

    }

    /**
     * @param Dom\HtmlNode $node
     */
    private function stripStyles($node)
    {
        /**
         * @var Attr
         */
        foreach($node->getAttributes() as $attribute) {

        }
    }


    /**
     * @param Spreadsheet $spreadsheet
     */
    private function initialiseSpreadsheet(&$spreadsheet)
    {
        $battingSheet = $spreadsheet->createSheet();
        $battingSheet->setTitle('First Innings');

        $this->addText($battingSheet, 'A1', 'Innings Of');
        $battingHeaders = [
            'Batter',
            'How Out',
            'Fielder 1',
            'How Out',
            'Fielder 2',
            'Runs',
            'Balls',
            'Fours',
            'Sixes'
        ];

        for($i = 1; $i <= sizeof($battingHeaders); $i++) {
            $cell = chr(64+$i) . '3';
            $this->addText($battingSheet, $cell, $battingHeaders[$i-1]);
        }

        $summaryFields = [
            'Byes',
            'Leg Byes',
            'No Balls',
            'Wides',
            'Penalty Runs',
            '',
            'Total',
            'for',
            'Overs'
        ];

        for($i = 0; $i < sizeof($summaryFields); $i++) {
            $cell = 'E' . ($i+17);
            $this->addText($battingSheet, $cell, $summaryFields[$i]);
        }

        $this->addText($battingSheet, 'A28', 'Fall of Wickets');
        $this->addText($battingSheet, 'A29', 'Score');
        $this->addText($battingSheet, 'B29', 'Batsman');

        $this->addText($battingSheet, 'A42', 'Bowling');

        $bowlingHeaders = [
          'Bowler','Overs','Maidens','Runs','Wickets','Wides','NoBalls','Fours','Sixes'
        ];

        for($i = 1; $i <= sizeof($bowlingHeaders); $i++) {
            $cell = chr(64+$i) . '43';
            $this->addText($battingSheet, $cell, $bowlingHeaders[$i-1]);
        }

        $battingSecondInningsSheet = clone $battingSheet;
        $battingSecondInningsSheet->setTitle('Second Innings');
        $spreadsheet->addSheet($battingSecondInningsSheet);

        // now do the opening overview sheet
        $overviewSheet = $spreadsheet->getSheet(0);
        $overviewSheet->setTitle('Overview');

        $headers = [
          'Home Club',
          'Away Club',
          'Home Team Suffix',
          'Away Team Suffix',
          'Ground',
          'Competition'
        ];

        for($i = 0; $i < sizeof($headers); $i++) {
            $cell = 'A' . ($i+1);
            $this->addText($overviewSheet, $cell, $headers[$i]);
        }
    }

    private function addText($sheet, $cell, $text)
    {
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}

