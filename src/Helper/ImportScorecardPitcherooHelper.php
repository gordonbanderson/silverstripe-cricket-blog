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

    /** @var Spreadsheet */
    private $spreadsheet;

    /** @var int 1 or 2 depending on whether first or second innings */
    private $innings;


    public function importScorecardFromURL($url)
    {
        $this->spreadsheet = new Spreadsheet();
        $this->initialiseSpreadsheet($this->spreadsheet);
        $this->parsePitcheroo($this->spreadsheet, $url);

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('test.xls');
    }


    private function parsePitcheroo( $url)
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

        $i = 0;

        for ($i = 1; $i <= 2; $i++) {
            $this->innings = $i;
            $teamBatting = $this->getTeamBatting($inningsScorecardHTML);
            $sheet = $this->spreadsheet->getSheet($this->innings);
            $sheet->setCellValue('B1', $teamBatting);

            $this->beingParsingCard($inningsScorecardHTML);
            $this->parseFallOfWickets($inningsScorecardHTML);
            $this->parseBowlingCard($inningsScorecardHTML);
        }
    }


    private function parseBowlingCard($inningsScorecardHTML)
    {
        $sheet = $this->spreadsheet->getSheet($this->innings);

        $level1Divs = $inningsScorecardHTML->find('div');
        $level3Divs = $level1Divs[2+$this->innings];
        $level2Divs = $level3Divs->find('div');
        $bowlingCard = $level2Divs[3];
        $entries = $bowlingCard->find('div');
        for ($i=1; $i<sizeof($bowlingCard); $i++) {
            $entry = $entries[$i];
            $entryDivs = $entry->find('div')[0]->find('div');

            $bowler = $entryDivs->find('div')[1]->find('div')[0]->innerHtml;
            $overs = $entryDivs[1]->innerHtml;
            $maidens = $entryDivs[2]->innerHtml;
            $runs = $entryDivs[3]->innerHtml;
            $wickets = $entryDivs[4]->innerHtml;

            $row = 43+$i;
            $sheet->setCellValue('A' . $row, $bowler);
            $sheet->setCellValue('B' . $row, $overs);
            $sheet->setCellValue('C' . $row, $maidens);
            $sheet->setCellValue('D' . $row, $runs);
            $sheet->setCellValue('E' . $row, $wickets);


            error_log("{$bowler}    O{$overs} M{$maidens} R{$runs} W{$wickets}");
        }

    }


    private function beingParsingCard($inningsScorecardHTML)
    {
        $level1Divs = $inningsScorecardHTML->find('div');
        //$battingCard = $level1Divs[3];
        $battingCard = $level1Divs[2+$this->innings];
        $this->exploreNodeset($battingCard);

        $level2Divs = $battingCard->find('div');
        $this->parseBattingCard($level2Divs[1]);
        error_log('+++++++');
        $this->exploreNodeset($level2Divs[1]);
    }

    public function parseFallOfWickets($inningsScorecardHTML)
    {
        $level1Divs = $inningsScorecardHTML->find('div');
        $fow = $level1Divs[2+$this->innings];
        $level2Divs = $fow->find('div');
        $sheet = $this->spreadsheet->getSheet($this->innings);

        $fowRows = $level2Divs[2]->find('div');
        for($i=1; $i<sizeof($fowRows);$i++) {
            $singleFowNode = $fowRows[$i];
            $subDivs = $singleFowNode->find('div');
            $fowScore = $subDivs[0]->innerHtml;
            $batsman = $subDivs[1]->innerHtml;
            error_log($batsman . ' - ' . $fowScore);
            $row = $i+29;
            $sheet->setCellValue('A' . $row, $fowScore);
            $sheet->setCellValue('B' . $row, $batsman);

        }
    }

    /**
     * The first child div can be ignored, the rest are batting details
     * @param Dom\HtmlNode $nodes Nodeset
     */
    private function parseBattingCard($nodes)
    {
        $batsmanDivs = $nodes->find('div');
        $sheet = $this->spreadsheet->getSheet($this->innings);
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

            $runs = $level4Divs[1]->innerHtml;
            $balls = $level4Divs[2]->innerHtml;
            $fours = $level4Divs[3]->innerHtml;
            $sixes = $level4Divs[4]->innerHtml;
            error_log('RUNS: ' . $runs);
            error_log('BALLS: ' . $balls);
            error_log('FOURS: ' . $fours);
            error_log('SIXES: ' . $sixes);
            error_log('----');
            $row = $i+3;
            $sheet->setCellValue('A' . $row, $batsmanName);
            $sheet->setCellValue('F' . $row, $runs);
            $sheet->setCellValue('G' . $row, $balls);
            $sheet->setCellValue('H' . $row, $fours);
            $sheet->setCellValue('I' . $row, $sixes);
        }

        error_log('I: ' . $i);
        // this contains 'Extras' with the breakdown as [0], [1] is the total of extras
        $extrasDiv = $batsmanDivs[$i]->find('div')[0];

        $extrasBreakdown = $extrasDiv->find('div')[0]->find('span')[0]->innerHtml;

        $totalExtras = $extrasDiv->find('div')[1]->innerHtml;
        error_log('EXTRAS: ' . $totalExtras);
        error_log('EXTRAS: ' . $extrasBreakdown);

        $totalsDiv = $batsmanDivs[$i+1]->find('div')[0];
        error_log('----------------------------------');
        error_log('TOTALS META: ' . $totalsDiv->find('span')[0]->innerHtml);
        error_log('TOTAL: ' . $totalsDiv->find('div')[1]->innerHtml);
    }

    private function getTeamBatting($inningsScorecardHTML)
    {
        $level1Divs = $inningsScorecardHTML->find('div');

        $card = $level1Divs[2+$this->innings];
        //$this->exploreNodeset($fow);
        //die;
        return $card->find('h3')[0]->innerHtml();
        error_log($h3->innerHtml);
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
    private function initialiseSpreadsheet()
    {
        $battingSheet = $this->spreadsheet->createSheet();
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
        $this->spreadsheet->addSheet($battingSecondInningsSheet);

        // now do the opening overview sheet
        $overviewSheet = $this->spreadsheet->getSheet(0);
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

