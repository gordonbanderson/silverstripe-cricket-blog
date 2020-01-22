<?php
namespace Suilven\CricketSite\Controller;

use SilverStripe\Control\HTTPRequest;
use Suilven\CricketSite\Model\Player;

class PlayersController extends \PageController
{
    private static $allowed_actions = [
        'indexNOT',
        'show',
    ];

    public function index(HTTPRequest $request) {
        $players = Player::get()->sort('Surname,FirstName');
        return [
            'Title' => 'Players',
            'Players' => $players
        ];
    }

    public function show(HTTPRequest $request) {
        $slug = $request->param('ID');
        $player = Player::get()->filter(['Slug' => $slug])->first();

        if(!$player) {
            return $this->httpError(404,'That region could not be found');
        }

        return [
            'Title' => $player->DisplayName,
            'Player' => $player
        ];
    }

}
