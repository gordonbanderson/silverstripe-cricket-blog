<?php

namespace Suilven\CricketSite\Model;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\ORM\ArrayList;
use SilverStripe\SiteConfig\SiteConfig;

class TeamSheetHelper
{
    private $headingColor;
    private $bylineColor;
    private $padding;
    private $fontSize;

    /**
     * @param Match $match
     */
    public function makeTeamSheet($match)
    {
        // style related
        // @todo Make these configurable
        $this->padding = 20;
        $this->headingColor = '#000099';
        $this->bylineColor = '#000099';
        $this->logo = 'themes/ssclient-core-theme/dist/img/furniture/cms-edit-logo.png';
        $playerSquareSize = 240;
        $playerPadding = 40;
        $topRowOffsetX = (1920-($playerSquareSize + $playerPadding)*6)/2 + $playerPadding / 2;
        $bottomRowOffsetX = (1920-($playerSquareSize + $playerPadding)*5)/2 + $playerPadding / 2;



        $this->ClubID = 1;
        $players = null;
        if ($match->HomeTeam()->Club()->ID == $this->ClubID) {
            error_log('**** HOME TEAM ****');
            $players = $match->HomeTeamPlayers();
        } else if ($match->AwayTeam()->Club()->ID == $this->ClubID) {
            error_log('**** AWAY TEAM ****');
            $players = $match->AwayTeamPlayers();
        } else {
            user_error('Neither home or away team is designated for team sheets');
        }

        $players = $players->toArray();
        $topRowY = 250; // @todo calculate this
        $secondRowY = $topRowY + $this->padding + $playerPadding + $playerSquareSize + 36;




        // match text
        $matchHeading = $match->matchHeading();
        $matchByline = $match->matchByLine();


        $manager = new ImageManager(['driver' => 'imagick']);
        $img = $manager->canvas(1920, 1080, '#fff');


        $img->insert('public/players.jpg', '',0,0);
        $img->greyscale()->blur(5);
        //rgb(176,48,96)
        $img->colorize(100, 38, 75);


        $this->writeText($img, $matchHeading, 970,$this->padding, 48);
        $this->writeText($img, $matchByline, 970,$this->padding + $this->fontSize * 1.2, 36);
        $img->insert($this->logo, '', $this->padding,$this->padding);

        // now, the players
        $positions = [
            [$topRowOffsetX, $topRowY],
            [$topRowOffsetX + $playerPadding + $playerSquareSize, $topRowY],
            [$topRowOffsetX + 2 * ($playerPadding + $playerSquareSize), $topRowY],
            [$topRowOffsetX + 3 * ($playerPadding + $playerSquareSize), $topRowY],
            [$topRowOffsetX + 4 * ($playerPadding + $playerSquareSize), $topRowY],
            [$topRowOffsetX + 5 * ($playerPadding + $playerSquareSize), $topRowY],

            [$bottomRowOffsetX, $secondRowY],
            [$bottomRowOffsetX + $playerPadding + $playerSquareSize, $secondRowY],
            [$bottomRowOffsetX + 2 * ($playerPadding + $playerSquareSize), $secondRowY],
            [$bottomRowOffsetX + 3 * ($playerPadding + $playerSquareSize), $secondRowY],
            [$bottomRowOffsetX + 4 * ($playerPadding + $playerSquareSize), $secondRowY],

        ];

        $config = SiteConfig::current_site_config();
        $emtpyPlayerImage = $config->EmptyPlayerImage();
        error_log('EMPTY PLAYER IMAGE: ' . $emtpyPlayerImage->getFilename());

        foreach($positions as $position) {
            $player = array_shift($players);
            error_log($player->DisplayName);

            /**
             * @var DBFile $photo
             */
            $photo = $player->PhotoID ? $player->Photo() : $emtpyPlayerImage;
            error_log('LINK:' . $photo->getFilename());
            error_log('SQUARE SIZE: ' . $playerSquareSize);
            $scaled = $photo->Fit($playerSquareSize, $playerSquareSize);
            error_log('SCALED:' . $scaled->getURL());

            $filepath = 'public/' . $scaled->getURL();
            error_log(print_r($position, 1));
            $x = $position[0];
            $y = $position[1];
            $img->insert($filepath, '',$x, $y);
            $textX = $x + $playerSquareSize/2;
            $textY = $y + $playerSquareSize + $this->padding;
            $this->writeText($img, $player->DisplayName, $textX, $textY, 24);
        }
        $img->save('public/test.png');
    }


    private function writeText($img, $text, $x, $y, $fontSize = 10)
    {
        $this->fontSize = $fontSize;
        $img->text($text, $x, $y, function ($font) {
            // $font->file('foo/bar.ttf');
            // @todo Make this configurable
            $font->file('public/Roboto-Regular.ttf');
            $font->size($this->fontSize);
            $font->color($this->headingColor);
            $font->align('center');
            $font->valign('top');
            //$font->angle(45);
        });
    }
}
