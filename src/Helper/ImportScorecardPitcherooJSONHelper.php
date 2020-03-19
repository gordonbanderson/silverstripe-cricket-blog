<?php

namespace Suilven\CricketSite\Helper;

use PHPHtmlParser\Dom;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Suilven\Sluggable\Helper\SluggableHelper;

class ImportScorecardPitcherooJSONHelper
{

    /** @var Spreadsheet */
    private $spreadsheet;

    /** @var int 1 or 2 depending on whether first or second innings */
    private $inningsCounter;

    private $teamNumber;

    /** @var string filename to save, will be date then teams slugged */
    private $filename;

    private $auccTeamSuffix;

    private $otherTeamSuffix;

    private function mkdir_if_required($dir)
    {
        if (!file_exists($dir) && !is_dir($dir)) {
            mkdir($dir);
        }
    }


    public function importScorecardFromURL($url, $teamNumber=1, $competitionAbbr, $auccTeamSuffix, $otherTeamSuffix)
    {
        $competitionName = str_replace('EPL', 'Eastern Premier League', $competitionAbbr);
        $competitionName = str_replace('SPCU', 'Breedon SPCU North East Championship (NEC)', $competitionName);
        $competitionName = str_replace('SC', 'Citylets Scottish Cup', $competitionName);

        $this->auccTeamSuffix = $auccTeamSuffix;
        $this->otherTeamSuffix = $otherTeamSuffix;

        error_log('Parsing ' . $url);
        error_log('======================================================');
        $this->mkdir_if_required('cache');

        $this->teamNumber = $teamNumber;

        $this->spreadsheet = new Spreadsheet();
        $this->initialiseSpreadsheet();
        $this->parsePitcheroo( $url, $competitionName);


        $writer = new Xlsx($this->spreadsheet);

        error_log('Saving to ' . $this->filename);
        $writer->save($this->filename);
    }


    private function parsePitcheroo( $url, $competitionName = '')
    {
        $hash = hash('SHA256', $url);
        $cacheFile = 'cache/' . $hash . '.html';

        if (!file_exists($cacheFile)) {
            $html = file_get_contents($url);
            file_put_contents($cacheFile, $html);
        }

        $dom = new Dom();

        $dom->loadFromFile($cacheFile, [
            'removeStyles' => true,
            'cleanupInput' => false,
            'preserveLineBreaks' => true
        ]);

        /** @var Dom\HtmlNode $shield */


        $scripts = $dom->find('script');
        /*
        $ctr = 0;
        foreach($scripts as $script) {
            error_log('-------' . $ctr . '-------');
            $ctr++;
            error_log($script->innerHtml);
        }
*/
        // most seem 3, some seem 4
      //  error_log($scripts[4]);

        $json = '';

        foreach($scripts as $script) {
            if(preg_match("/initialReduxState/i", $script)) {
                error_log('initialReduxState found');
                $json = $script->innerHtml;
                break;
            }
        }
        $details = json_decode($json, true);


        //obj.props.initialReduxState.teams.matchCentre.pageData


        $details = $details['props']['initialReduxState']['teams']['matchCentre'];
        $key = $details['loadedPages'][0];
        $matchDetails = $details['pageData'][$key];

        //error_log('MD: ' . print_r($matchDetails, 1));

        $homeTeamClub = $matchDetails['overview']['home']['name'];
        $homeTeamClub = $this->normalizeClubName($homeTeamClub);

        error_log('HOME TEAM=' . $homeTeamClub);

        $awayTeamClub = $matchDetails['overview']['away']['name'];
        $awayTeamClub = $this->normalizeClubName($awayTeamClub);
        $date = $matchDetails['overview']['date'];

        if ($homeTeamClub == 'Arbroath United Cricket Club') {
            $homeTeamSuffix = $this->auccTeamSuffix;
            $awayTeamSuffix = $this->otherTeamSuffix;
        } else if ($awayTeamClub == 'Arbroath United Cricket Club') {
            $homeTeamSuffix = $this->otherTeamSuffix;
            $awayTeamSuffix =  $this->auccTeamSuffix;
        } else {
            error_log('Arbroath United Cricket Club not found as either of teams');
            die;
        }


        $ground = isset($matchDetails['location']['name']) ? $matchDetails['location']['name'] : '';

        $sheet = $this->spreadsheet->getSheet(0);
        $sheet->setCellValue('B1', $homeTeamClub);
        $sheet->setCellValue('B2', $awayTeamClub);
        $sheet->setCellValue('B3', $homeTeamSuffix);
        $sheet->setCellValue('B4', $awayTeamSuffix);
        $sheet->setCellValue('B5', $ground);
        $sheet->setCellValue('B6', $competitionName);

        $splits = explode('-', $date);
        $formattedDate = $date;
        $sheet->setCellValue('B8', $splits[2] . '/' . $splits[1] . '/' . $splits[0]);

        // season
        $season =  $splits[0];
        $sheet->setCellValue('B7',$season);

        $scorecard = $matchDetails['scorecard'];

        $toss = $scorecard['toss'];

        //error_log(print_r($scorecard, 1));



        $typeShort = '';
        if (isset($matchDetails['overview']['typeShort'])) {
            $typeShort = $matchDetails['overview']['typeShort'];
        }

        if (empty($typeShort)) {
            $typeShort = 'unknown';
        }
        error_log('Type short: ' . $typeShort);

        $helper = new SluggableHelper();
        $this->mkdir_if_required('spreadsheets');
        $this->mkdir_if_required('spreadsheets/' . $this->teamNumber);
        $this->mkdir_if_required('spreadsheets/' . $this->teamNumber .'/' . $season);
        $this->mkdir_if_required('spreadsheets/' . $this->teamNumber .'/' . $season . '/' . $typeShort);
        $this->filename = 'spreadsheets/' . $this->teamNumber .'/' . $season . '/' . $typeShort . '/' .
            $helper->getSlug($date . ' ' . $homeTeamClub . ' ' . $awayTeamClub) . '.xls';

      //  error_log('FN: ' . $this->filename);

        // won by N wickets
        if (isset($matchDetails['report']) && isset($matchDetails['report']['tagline'])) {
            $sheet->setCellValue('B9', $matchDetails['report']['tagline']);
        }

        $sheet->setCellValue('B10', $url);


        for ($i = 1; $i <= 2; $i++) {
            $this->inningsCounter = $i;
            $innings = $scorecard['innings'][$i-1];
            $teamBatting = $innings['batting']['name'];
            $teamBatting = $this->normalizeClubName($teamBatting);
            $sheet = $this->spreadsheet->getSheet($this->inningsCounter);
            $sheet->setCellValue('B1', $teamBatting);

           $this->parseBattingCard($innings);
           $this->parseFallOfWickets($innings);
           $this->parseBowlingCard($innings);
        }

        $reportSheet = $this->spreadsheet->getSheet(3);
        if (isset($matchDetails['report']['title'])) {
            $reportSheet->setCellValue('A2', $matchDetails['report']['title']);
        }

        if (isset($matchDetails['report']['html'])) {
            $reportSheet->setCellValue('B2', $matchDetails['report']['html']);
        }

        if ($toss) {
            $reportSheet->setCellValue('C2', $toss);
        }

    }


    private function parseBowlingCard($innings)
    {
        $sheet = $this->spreadsheet->getSheet($this->inningsCounter);
        $bowlers = $innings['bowlers'];
        $nEntries = count($bowlers);
        for ($i=1; $i<=$nEntries; $i++) {
            $bowler = $bowlers[$i-1];
            $bowler = $this->normalizePlayer($bowler);
            $row = 43+$i;
            $sheet->setCellValue('A' . $row, $bowler['name']);
            $sheet->setCellValue('B' . $row, $bowler['overs']);
            $sheet->setCellValue('C' . $row, $bowler['maidens']);
            $sheet->setCellValue('D' . $row, $bowler['runs']);
            $sheet->setCellValue('E' . $row, $bowler['wickets']);
            $sheet->setCellValue('F' . $row, $bowler['wides']);
            $sheet->setCellValue('G' . $row, $bowler['noBalls']);
        }

    }


    private function parseBattingCard($innings)
    {
        $sheet = $this->spreadsheet->getSheet($this->inningsCounter);
        $batting = $innings['batting'];
        $batsmen = $batting['batsmen'];
        $nBatsmen = count($batsmen);
        for ($i=1; $i<=$nBatsmen; $i++) {
            $batsmanEntry = $batsmen[$i-1];

            $row = $i+3;
            $name = $batsmanEntry['name'];
            $name = $this->normalizePlayer($name);
            $sheet->setCellValue('A' . $row, $name);

            $splits = explode(',', $batsmanEntry['dismissal']);
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


            $sheet->setCellValue('B' . $row, $dismissal[0]);
            $sheet->setCellValue('C' . $row, $dismissal[1]);
            $sheet->setCellValue('D' . $row, $dismissal[2]);
            $sheet->setCellValue('E' . $row, $dismissal[3]);

            $sheet->setCellValue('F' . $row, $batsmanEntry['runs']);
            $sheet->setCellValue('G' . $row, $batsmanEntry['ballsFaced']);
            $sheet->setCellValue('H' . $row, $batsmanEntry['fours']);
            $sheet->setCellValue('I' . $row, $batsmanEntry['sixes']);
        }

        // deal with the extras

        /*
         * "summary": "For 10 wickets, 45.2 overs",
                      "extrasSummary": "13w, 2lb",
                      "byes": 0,
                      "overs": "45.2",
                      "noBalls": 0,
                      "penaltyRuns": 0,
                      "legByes": 2,
                      "name": "Arbroath United CC",
         */

        $sheet->setCellValue('F17', $batting['byes']);
        $sheet->setCellValue('F18', $batting['legByes']);
        $sheet->setCellValue('F19', $batting['noBalls']);
        $sheet->setCellValue('F20', $batting['wides']);
        $sheet->setCellValue('F21', $batting['penaltyRuns']);
        $sheet->setCellValue('F23', $batting['runs']);
        $sheet->setCellValue('F24', $batting['wickets']);
        $sheet->setCellValue('F25', $batting['overs']);
    }

    /**
     * @param $innings
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parseFallOfWickets($innings)
    {
        $sheet = $this->spreadsheet->getSheet($this->inningsCounter);

        $fow = $innings['fow'];

        $nFow = count($fow);
        for($i=1; $i<=$nFow;$i++) {
            $row = $i+29;
            $sheet->setCellValue('A' . $row, $fow[$i-1]['teamScore']);
            $batsman = $fow[$i-1]['batsmanName'];
            $batsman = $this->normalizePlayer($batsman);
            $sheet->setCellValue('B' . $row, $batsman);
        }
    }


    private function normalizeClubName($clubName)
    {
        $result = $clubName;
        $result = str_replace('Arbroath United CC', 'Arbroath United Cricket Club', $result);
        $result = str_replace('RH Corstorphine', 'Royal High Corstorphine', $result);
        $result = str_replace('Strathmore X1', 'Strathmore', $result);
        $result = str_replace('Dunferline Wanderers', 'Dunfermline Wanderers', $result);
        $result = str_replace('Forfarshire', 'ion8 Forfarshire', $result);
        $result = str_replace('Mazers Grange', 'Mazars Grange', $result);
        $result = str_replace('Stoneywood / Dyce', 'Stoneywood Dyce', $result);
        $result = str_replace('Fred', 'Fred', $result);
        $result = str_replace('Fred', 'Fred', $result);
        $result = str_replace('Fred', 'Fred', $result);
        $result = str_replace('Fred', 'Fred', $result);
        $result = str_replace('Fred', 'Fred', $result);
        $result = str_replace('Fred', 'Fred', $result);

        return $result;
    }


    private function normalizeDismissal($dismissalPortion)
    {
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
            'Caught' => 'c',
            'st.' => 'st',
          'lb.' => 'lbw',
            'Not Out' => 'not out',
            'ro.' => 'run out',
            'Run Out' => 'run out',
            'Did Not Bat' => 'did not bat'
        ];

        $keys = array_keys($normalized);
        $result = 'UNKNOWN';
        $fielder = '';


        if (in_array($dismissalPortion, $keys)) {
            $result = $normalized[$dismissalPortion];
        }

        if ($result == 'UNKNOWN') {
            $splits = explode(' ', trim($dismissalPortion));

            if (in_array($splits[0], $keys)) {
                if (isset($normalized[$splits[0]])) {
                    $result = $normalized[$splits[0]];
                }

                array_shift($splits);
                $fielder = implode(' ', $splits);
            }
        }

        // @todo make this configurable
        $fielder = $this->normalizePlayer($fielder);




        return [
            'method' => $result,
            'fielder' => $fielder
        ];
    }


    private function normalizePlayer($fielder)
    {
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
        $fielder = str_replace('H Carnegie', 'Harris Carnegie', $fielder);
        $fielder = str_replace('D Bridges', 'Dave Bridges', $fielder);
        $fielder = str_replace('J Russell', 'Jamie Russell', $fielder);
        $fielder = str_replace('P Stewart', 'Paul Stewart', $fielder);
        $fielder = str_replace('C Tait', 'Conor Tait', $fielder);
        $fielder = str_replace('L Lawrence', 'Logan Lawrence', $fielder);
        $fielder = str_replace('Z Rennie', 'Zoe Rennie', $fielder);
        $fielder = str_replace('R Duthie', 'Rhys Duthie', $fielder);
        $fielder = str_replace('R Banks-Hawley', 'Rohan Banks-Hawley', $fielder);
        $fielder = str_replace('S Hawley', 'Simon Hawley', $fielder);
        $fielder = str_replace('K Ritchie', 'Kevin Ritchie', $fielder);
        $fielder = str_replace('B Allchin', 'Bryce Allchin', $fielder);
        $fielder = str_replace('B O&#x27;Mara', "Ben O'Mara", $fielder);
        $fielder = str_replace('F Burnett', 'Fraser Burnett', $fielder);
        $fielder = str_replace('R McLean', 'Ross McLean', $fielder);
        $fielder = str_replace('S Vithanawasam', 'Shanuka Vithanawasam', $fielder);
        $fielder = str_replace('J Waller', 'Jack Waller', $fielder);

        # Heriots
        // Chris Ashforth or Chris Ashworth?

        $fielder = str_replace('J Potgieter', 'Johann Potgieter', $fielder);
        $fielder = str_replace('P Ross', 'Peter Ross', $fielder);
        $fielder = str_replace('E Ruthven', 'Elliot Ruthven', $fielder);
        $fielder = str_replace('M Shean', 'Michael Shean', $fielder);
        $fielder = str_replace('C Ashforth', 'Chris Ashforth', $fielder);
        $fielder = str_replace('R Brown', 'Ryan Brown', $fielder);
        $fielder = str_replace('H van der Berg', 'Hayes van der Berg', $fielder);

        $fielder = str_replace('K Morton', 'Keith Morton', $fielder);
        $fielder = str_replace('R More', 'Robert More', $fielder);
        $fielder = str_replace('E Meiri', 'Elnathan Meiri', $fielder);

        # RHC
        $fielder = str_replace('M Haq', 'Majid Haq', $fielder);
        $fielder = str_replace('E Foster', 'Elliot Foster', $fielder);
        $fielder = str_replace('C Clarkson', 'Calum Clarkson', $fielder);
        $fielder = str_replace('C Whitefoord', 'Caleb Whitefoord', $fielder);
        $fielder = str_replace('M Saad', 'Mohammad Saad', $fielder);
        $fielder = str_replace('J Wood', 'Jacob Wood', $fielder);
        $fielder = str_replace('C Dutia', 'Callum Dutia', $fielder);

        # Stoneywood Dyce
        $fielder = str_replace('Euan Davidson', 'Ewan Davidson', $fielder);
        $fielder = str_replace('Prashat Wig', 'Prashant Wig', $fielder);
        $fielder = str_replace('Player', 'Player', $fielder);
        $fielder = str_replace('Player', 'Player', $fielder);

        // to research
        $fielder = str_replace('A Khan', 'Ali Khan', $fielder);

        return $fielder;
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

        $nBowlingHeaders = count($battingHeaders);
        for($i = 1; $i < $nBowlingHeaders; $i++) {
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

        $nSummaryFields = count($summaryFields);
        for($i = 0; $i < $nSummaryFields; $i++) {
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

        $nBowlingHeaders = count($bowlingHeaders);
        for($i = 1; $i < $nBowlingHeaders; $i++) {
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
          'Competition',
            'Season',
            'Date',
            'Summary',
            'Source'
        ];

        $nHeaders = count($headers);
        for($i = 0; $i < $nHeaders; $i++) {
            $cell = 'A' . ($i+1);
            $this->addText($overviewSheet, $cell, $headers[$i]);
        }

        $reportSheet = $this->spreadsheet->createSheet();
        $reportSheet->setTitle('Report');
        $this->addText($reportSheet, 'A1', 'Title');
        $this->addText($reportSheet, 'B1', 'Report');
        $this->addText($reportSheet, 'C1', 'Toss');
    }

    private function addText($sheet, $cell, $text)
    {
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}

