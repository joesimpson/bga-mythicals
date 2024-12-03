<?php

namespace Bga\Games\Mythicals\Managers;

use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Models\Player;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends \Bga\Games\Mythicals\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \Bga\Games\Mythicals\Models\Player($row);
  }

  /**
   * @param array $players
   * @return Collection $players
   */
  public static function setupNewGame($players, $options)
  {
    // Create players
    $gameInfos = Game::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    shuffle($colors);//Shuffle for cases where color matters
    $query = self::DB()->multipleInsert(['player_id', 'player_color', 'player_canal', 'player_name', 'player_avatar']);

    $query_values = [];
   
    foreach ($players as $player_id => $player) {
      // Now you can access both $player_id and $player array
      $color = array_shift($colors);
      $query_values[] = [
          $player_id,
          $color,
          $player["player_canal"],
          addslashes($player["player_name"]),
          addslashes($player["player_avatar"]),
      ];
    }
    $query->values($query_values);

    $playersObjects = self::getAll();
    if($gameInfos['favorite_colors_support'] ){
      Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);

      //Reload DAO 
      $playersObjects = self::getAll();
      foreach ($playersObjects as $pId => $player) {
        //$color = Players::getColor($pId);
        $color = $player->getColor(); 
      }
    }
    Game::get()->reloadPlayersBasicInfos();
    
    return $playersObjects;
  }
 
  /**
   * Setup new player turn
   */
  public static function setupNewTurn($player)
  {
    Stats::inc("turns_number_p",$player);
  }
  /**
   * @param int $pId
   * @param int $score score to add to current score
   */
  public static function incPlayerScore($pId, $score)
  {
    Game::get()->trace("incPlayerScore($pId)");

    return self::DB()
      ->inc(['player_score' => $score])
      ->wherePlayer($pId)
      ->run();
  }

  public static function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public static function getCurrentId($bReturnNullIfNotLogged = false)
  {
    return (int) Game::get()->getCurrentPId($bReturnNullIfNotLogged);
  }

  public static function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public static function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  /**
   * @return Player
   */
  public static function getActive()
  {
    return self::get();
  }

  /**
   * @return Player
   */
  public static function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public static function getNextId($player = null)
  {
    $player = $player ?? Players::getCurrent();
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }
  
  /**
   * @param int $player_id
   * @return Player
   */
  public static function getNextPlayerNotEliminated(int $player_id)
  {
    $nextPlayer_id = Players::getNextId($player_id);
    $nextPlayer = Players::get($nextPlayer_id);
    if(isset($nextPlayer) 
      && $nextPlayer->getZombie() != 1 && $nextPlayer->getEliminated() == 0
    ){
      return $nextPlayer;
    }
    return self::getNextPlayerNotEliminated($nextPlayer_id);
  }

  /**
   * @param int $pId
   * @return String color : the up-to date player color
   */
  public static function getColor($pId)
  {
    return self::DB()->wherePlayer($pId)
      ->get()
      ->first()->getPlayerColor();
  }

  /*
   * Return the number of players
   */
  public static function count()
  {
    return self::DB()->count();
  }

  /*
   * getUiData : get all ui data of all players
   */
  public static function getUiData($pId)
  {
    return self::getAll()
      ->map(function ($player) use ($pId) {
        return $player->getUiData($pId);
      })
      ->toAssoc();
  }

  /**
   * Get current turn order according to first player variable
   */
  public static function getTurnOrder($firstPlayer = null)
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }

  /**
   * This allow to change active player
   */
  public static function changeActive($pId)
  {
    Game::get()->gamestate->changeActivePlayer($pId);
  }

  /**
   * Sets player datas related to turn number $turn
   * @param array $player_ids
   * @param int $turn
   */
  public static function startTurn($player_ids,$turn)
  {
    foreach($player_ids as $player_id){
      $player = self::get($player_id);
      $player->startTurn($turn);
    }
  }
}
