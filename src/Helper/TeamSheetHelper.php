<?php

namespace Suilven\CricketSite\Helper;

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
        $this->logo = 'themes/ssclient-core-theme/dist/img/furniture/logo-trans.png';
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
        $topRowY = 240+2*$this->padding; // @todo calculate this
        $secondRowY = $topRowY + $this->padding + $playerPadding + $playerSquareSize + 36;




        // match text
        $matchHeading = $match->matchHeading();
        $matchByline = $match->matchByLine();


        $manager = new ImageManager(['driver' => 'imagick']);
        $img = $manager->canvas(1920, 1080, '#fff');


        $img->insert('public/aucc-bg-2.jpg', '',0,0);
       // $img->greyscale()->blur(5);
      //  $img->colorize(100, 38, 75);


        $this->writeText($img, $matchHeading, 970,$this->padding, 48);
        $this->writeText($img, $matchByline, 970,$this->padding + $this->fontSize * 1.2, 36);
        $img->insert($this->logo, '', $this->padding,$this->padding);

        $this->headingColor = '#800';

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

            /** @var \SilverStripe\Assets\Image $scaled */
            $scaled = $photo->Fit($playerSquareSize, $playerSquareSize);
            error_log('SCALED:' . $scaled->getURL());

            $scaledWidth = $scaled->getWidth();

            $filepath = 'public/' . $scaled->getURL();
            error_log(print_r($position, 1));
            $x = $position[0] ; // ;
            $y = $position[1];
            $img->insert($filepath, '',$x + ($playerSquareSize - $scaledWidth)/2, $y);
            $textX = $x + $playerSquareSize/2;
            $textY = $y + $playerSquareSize + $this->padding;
            $this->writeText($img, $player->DisplayName, $textX, $textY, 24);
        }



        // sponsor logos
        // @todo this should be an extension
        // hardwired positions

        // 248x89
        $img->insert('themes/ssclient-core-theme/dist/img/furniture/sponsor-tailend-teamsheet.png',
            '',
            1920-248-$this->padding,
            1080-89-$this->padding
        );

        $img->insert('themes/ssclient-core-theme/dist/img/furniture/sponsor-angus-soft-fruits-teamsheet.gif',
            '',
            $this->padding,
            1080-120-$this->padding
        );

        $img->insert('themes/ssclient-core-theme/dist/img/furniture/citylets-logo.png',
            '',
            1920-300-$this->padding,
            3*$this->padding
        );


        // footer things
        // @todo Extension
        $img->insert('public/pavilion.png',
            '',
            586,
            1080-140
        );

        $img->save('public/test.png');

    }


    private function writeText($img, $text, $x, $y, $fontSize = 10)
    {
        $this->fontSize = $fontSize;
        $img->text($text, $x, $y, function ($font) {
            // $font->file('foo/bar.ttf');
            // @todo Make this configurable
            $font->file('public/Roboto-Medium.ttf');
            $font->size($this->fontSize);
            $font->color($this->headingColor);
            $font->align('center');
            $font->valign('top');
            //$font->angle(45);
        });
    }
}
