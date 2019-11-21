<?php

namespace Suilven\CricketSite\Helper;

use PHPHtmlParser\Dom;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportScorecardPitcherooHelper
{

    /** @var Spreadsheet */
    private $spreadsheet;

    /** @var int 1 or 2 depending on whether first or second innings */
    private $innings;


    public function importScorecardFromURL($url)
    {
        $this->spreadsheet = new Spreadsheet();
        $this->initialiseSpreadsheet();
        $this->parsePitcheroo( $url);

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('test.xls');
    }


    private function parsePitcheroo( $url)
    {
        $dom = new Dom();

        $dom->loadFromUrl($url, [
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

        $level2Divs = $battingCard->find('div');
        $this->parseBattingCard($level2Divs[1]);
        error_log('+++++++');
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
            $fowScoreAndWickets = $subDivs[0]->innerHtml;
            $fowScore = explode('-', $fowScoreAndWickets)[0];
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

            // $level4Divs[0] contains the avatar, batsman name and method of dismissal
            $avatarBlock = $level4Divs[0]->find('div')[1];
            if (is_null($avatarBlock)) {
                break;
            }
            $batsmanNameDiv = $avatarBlock->find('div')[0];

            $batsmanName = $batsmanNameDiv->innerHtml;
            error_log('BATSMAN:' . $batsmanName);
            $dismissalDesc = $avatarBlock->find('span')[0]->innerHtml;
            error_log('DISMISSAL: ' . $dismissalDesc);

            $splits = explode(',', $dismissalDesc);
            $dismissal = ['','','',''];
            if (sizeof($splits) == 1) {
                $info = $this->normalizeDismissal($splits[0]);
                $dismissal[2] = $info['method'];
                $dismissal[3] = $info['fielder'];
            } else {
                // this is cases like c. Fred Smith,  b. Simon Jones
                $info0 = $this->normalizeDismissal($splits[0]);
                $info1 = $this->normalizeDismissal($splits[1]);
                $dismissal[0] = $info0['method'];
                $dismissal[1] = $info0['fielder'];
                $dismissal[2] = $info1['method'];
                $dismissal[3] = $info1['fielder'];
            }



            $runs = $level4Divs[1]->innerHtml;
            $balls = $level4Divs[2]->innerHtml;
            $fours = $level4Divs[3]->innerHtml;
            $sixes = $level4Divs[4]->innerHtml;
            error_log('RUNS: ' . $runs);
            error_log('BALLS: ' . $balls);
            error_log('FOURS: ' . $fours);
            error_log('SIXES: ' . $sixes);
            error_log('----');
            error_log(print_r($dismissal, 1));
            $row = $i+3;
            $sheet->setCellValue('A' . $row, $batsmanName);
            $sheet->setCellValue('B' . $row, $dismissal[0]);
            $sheet->setCellValue('C' . $row, $dismissal[1]);
            $sheet->setCellValue('D' . $row, $dismissal[2]);
            $sheet->setCellValue('E' . $row, $dismissal[3]);
            $sheet->setCellValue('F' . $row, $runs);
            $sheet->setCellValue('G' . $row, $balls);
            $sheet->setCellValue('H' . $row, $fours);
            $sheet->setCellValue('I' . $row, $sixes);
        }

        error_log('I: ' . $i);
        // this contains 'Extras' with the breakdown as [0], [1] is the total of extras
        $extrasDiv = $batsmanDivs[$i]->find('div')[0];

        $extrasBreakdownText = $extrasDiv->find('div')[0]->find('span')[0]->innerHtml;

        $extrasArray = $this->getExtrasBreakdown($extrasBreakdownText);

        $totalExtras = $extrasDiv->find('div')[1]->innerHtml;
        error_log('EXTRAS: ' . $totalExtras);

        $sheet->setCellValue('F17', $extrasArray['b']);
        $sheet->setCellValue('F18', $extrasArray['lb']);
        $sheet->setCellValue('F19', $extrasArray['nb']);
        $sheet->setCellValue('F20', $extrasArray['w']);
        $sheet->setCellValue('F21', $extrasArray['pr']);


        $totalsDiv = $batsmanDivs[$i+1]->find('div')[0];
        error_log('----------------------------------');

        // this is likes of "(For 8 wickets, 50 overs)"
        $totalsMeta =  $totalsDiv->find('span')[0]->innerHtml;
        $splits = explode(' ', $totalsMeta);

        $sheet->setCellValue('F23', $totalsDiv->find('div')[1]->innerHtml);
        $sheet->setCellValue('F24', $splits[1]);
        $sheet->setCellValue('F25', $splits[3]);
    }

    private function normalizeDismissal($dismissalPortion)
    {
        error_log('METHOD: ' . $dismissalPortion);
        /*
         *  b
 c
 st
 c&b
 lbw
 retired
 retired hurt
 hit the ball twice
 hit wicket
 did not bat
 obstructing the field
 timed out
 not out

         */
        $normalized = [
            'b.' => 'b',
            'ct.' => 'c',
            'Caught.' => 'c',
            'st.' => 'st',
          'lb.' => 'lbw',
            'Not Out' => 'not out',
            'ro.' => 'run out',
            'Run Out' => 'run out',

        ];

        $keys = array_keys($normalized);
        $result = 'UNKNOWN';
        $fielder = '';

        error_log("\tT1 Checking for existence of {$dismissalPortion}");

        if (in_array($dismissalPortion, $keys)) {
            $result = $normalized[$dismissalPortion];
        }

        if ($result == 'UNKNOWN') {
            $splits = explode(' ', trim($dismissalPortion));
            error_log(print_r($splits, 1));
            error_log("\tT2 Checking for existence of *{$splits[0]}*");

            if (in_array($splits[0], $keys)) {
                if (isset($normalized[$splits[0]])) {
                    $result = $normalized[$splits[0]];
                }

                array_shift($splits);
                $fielder = implode(' ', $splits);
            }
        }

        // @todo make this configurable
        $fielder = str_replace('W Lubbe', 'Wihan Lubbe', $fielder);
        $fielder = str_replace('M Petrie', 'Marc Petrie', $fielder);
        $fielder = str_replace('S Christie', 'Shaun Christie', $fielder);
        $fielder = str_replace('D Salmond', 'Daniel Salmond', $fielder);
        $fielder = str_replace('J Plomer', 'Jack Plomer', $fielder);
        $fielder = str_replace('B Plomer', 'Ben Plomer', $fielder);
        $fielder = str_replace('R Plomer', 'Ryan Plomer', $fielder);
        $fielder = str_replace('D Sinclair', 'Daryl Sinclair', $fielder);
        $fielder = str_replace('C Ramsay', 'Craig Ramsay', $fielder);
        $fielder = str_replace('C Cameron', 'Craig Cameron', $fielder);
        $fielder = str_replace('R Cameron', 'Ryan Cameron', $fielder);
        $fielder = str_replace('E Small', 'Euan Small', $fielder);
        $fielder = str_replace('F Snyman', 'Frederik Snyman', $fielder);
        $fielder = str_replace('G Peal', 'Greig Peal', $fielder);
        $fielder = str_replace('M Parker', 'Matthew Parker', $fielder);
        $fielder = str_replace('H Laing', 'Hayden Laing', $fielder);
        $fielder = str_replace('A Brewer', 'Adam Brewer', $fielder);
        $fielder = str_replace('C Robb', 'Chris Robb', $fielder);
        $fielder = str_replace('C Burnett', 'Calvin Burnett', $fielder);
        $fielder = str_replace('C Ross', 'Craig Ross', $fielder);
        $fielder = str_replace('J Burnett', 'Jon Burnett', $fielder);
        $fielder = str_replace('A Hogg', 'Abbie Hogg', $fielder);
        $fielder = str_replace('M Clark', 'Murray Clark', $fielder);
        $fielder = str_replace('M Salmond', 'Matthew Salmond', $fielder);
        $fielder = str_replace('J Salmond', 'John Salmond', $fielder);
        $fielder = str_replace('L Paterson', 'Lee Paterson', $fielder);
        $fielder = str_replace('R Paterson', 'Ross Paterson', $fielder);
        $fielder = str_replace('A Davidson', 'Alex Davidson', $fielder);
        $fielder = str_replace('M Robb', 'Murray Robb', $fielder);
        $fielder = str_replace('K Stott', 'Kevin Stott', $fielder);
        $fielder = str_replace('M McColl', 'Megan McColl', $fielder);
        $fielder = str_replace('B Carnegie', 'Bryce Carnegie', $fielder);
        $fielder = str_replace('D Bridges', 'Dave Bridges', $fielder);
        $fielder = str_replace('J Russell', 'Jamie Russell', $fielder);
        $fielder = str_replace('P Stewart', 'Paul Stewart', $fielder);
        $fielder = str_replace('C Tait', 'Conor Tait', $fielder);
        $fielder = str_replace('L Lawrence', 'Logan Lawrence', $fielder);
        $fielder = str_replace('Z Rennie', 'Zoe Rennie', $fielder);
        $fielder = str_replace('R Duthie', 'Rhys Duthie', $fielder);
        $fielder = str_replace('R Banks-Hawley', 'Rohan Banks-Hawley', $fielder);
        $fielder = str_replace('S Hawley', 'Simon Hawley', $fielder);
        $fielder = str_replace('B O&#x27;Mara', "Ben O'Mara", $fielder);


        return [
            'method' => $result,
            'fielder' => $fielder
        ];
    }


    private function getExtrasBreakdown($extrasBreakdown)
    {
        error_log('INPUT: ' . $extrasBreakdown);

        $result = [
          'b' => 0,
            'lb' => 0,
            'nb' => 0,
            'w' => 0,
            'pr' => 0
        ];
        $extrasBreakdown = ltrim($extrasBreakdown, '(');
        $extrasBreakdown = rtrim($extrasBreakdown, ')');

        $splits = explode(',', $extrasBreakdown);
        foreach($splits as $extrasDetails) {
            error_log('ED: ' . $extrasDetails);
            $rowSplit = preg_split('/(\d+)/', $extrasDetails, -1, PREG_SPLIT_DELIM_CAPTURE);
            error_log(print_r($rowSplit,1));
            $result[$rowSplit[2]] = $rowSplit[1];
        }

        return $result;

    }

    private function getTeamBatting($inningsScorecardHTML)
    {
        $level1Divs = $inningsScorecardHTML->find('div');
        $card = $level1Divs[2+$this->innings];
        return $card->find('h3')[0]->innerHtml();
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

