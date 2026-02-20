<?php

namespace Bga\Games\MythicalsTheBoardGame\Models;

use Bga\Games\MythicalsTheBoardGame\Game;
use Bga\Games\MythicalsTheBoardGame\Core\Notifications;
use Bga\Games\MythicalsTheBoardGame\Core\Stats;
use Bga\Games\MythicalsTheBoardGame\Managers\Cards;
use Bga\Games\MythicalsTheBoardGame\Managers\Players;
use Bga\Games\MythicalsTheBoardGame\Managers\Tiles;
use Bga\Games\MythicalsTheBoardGame\Managers\Tokens;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \Bga\Games\MythicalsTheBoardGame\Helpers\DB_Model
{
  private $map = null;
  protected $table = 'player';
  protected $primary = 'player_id';
  protected $attributes = [
    'id' => ['player_id', 'int'],
    'no' => ['player_no', 'int'],
    'name' => 'player_name',
    'color' => 'player_color',
    'eliminated' => 'player_eliminated',
    'score' => ['player_score', 'int'],
    'scoreAux' => ['player_score_aux', 'int'],
    'zombie' => 'player_zombie',

    //GAME SPECIFIC : 
  ];

  public function getUiData($currentPlayerId = null)
  {
    $data = parent::getUiData();
    $data['nbcards'] = Cards::countPlayerCards($this->getId(), CARD_LOCATION_HAND);
    $data['nbtiles'] = Tiles::countPlayerTiles($this->getId(), TILE_LOCATION_HAND);
    $data['nbtokens'] = Tokens::countPlayerTokens($this->getId(), TOKEN_LOCATION_HAND);
 
    return $data;
  }

  public function getPref($prefId)
  {
    //return Preferences::get($this->id, $prefId);
    //BGA framework :
    $pref_value = Game::get()->bga->userPreferences->get($this->getId(),$prefId);
    return $pref_value;
  }

  public function getStat($name)
  {
    $name = 'get' . \ucfirst($name);
    return Stats::$name($this->getId());
  }
  
  /**
   * @param int $points
   * @param bool $sendNotif (Default true)
   */
  public function addPoints($points, $sendNotif = true)
  {
    if($points == 0) return;
    //$this->setScore( $this->getScore() + $points);
    //$this->incScore($points); // SAME as previous
    //REAL INC in DB in case of not up to date score in object
    Players::incPlayerScore($this->getId(), $points);
    Stats::inc( "score", $this->getId(), $points );
    if($sendNotif) Notifications::addPoints($this,$points);
  }

  public function setTieBreakerPoints($points)
  {
    $this->setScoreAux($points);
  }
  public function addTieBreakerPoints($points)
  {
    if($points == 0) return;
    $this->incScoreAux($points);
  }

  /**
   * Sets player datas related to turn number $turnIndex
   * @param int $turnIndex
   */
  public function startTurn($turnIndex)
  { 
  }
  
  public function giveExtraTime(){
    Game::get()->giveExtraTime($this->getId());
  }
  

  
  public function getNbTokens(): int 
  {
    return Tokens::countPlayerTokens($this->getId(), TOKEN_LOCATION_HAND);
  }
}
