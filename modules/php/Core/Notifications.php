<?php

namespace Bga\Games\Mythicals\Core;

use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Models\MasteryTile;
use Bga\Games\Mythicals\Models\Player;

class Notifications
{ 
  
  /**
   * @param Player $player
   * @param int $points
   * @param string $msg (optional) Message to overwrite default
   */
  public static function addPoints($player,$points, $msg = null){
    if(!isset($msg)) $msg = clienttranslate('${player_name} scores ${n} ${points}');
    self::notifyAll('addPoints',$msg,[ 
        'player' => $player,
        'n' => $points,
        'points' => 'points',
      ],
    );
  }

  /**
   */
  public static function computeFinalScore()
  {
    self::notifyAll('computeFinalScore', clienttranslate('Computing final scoring...'), [
    ]);
  }

  /**
   * @param Player $player
   * @param Card $card
   */
  public static function cardToReserve($player, $card)
  {
    self::notifyAll('cardToReserve', clienttranslate('A new card goes to the reserve'), [
      //'player' => $player,
      'card' => $card->getUiData(),
    ]);
  }
  
  /**
   * @param Player $player
   * @param Card $card
   */
  public static function giveCardTo($player, $card)
  {
    self::notifyAll('giveCardToPublic', clienttranslate('${player_name} receives a new card'), [
      'player' => $player,
    /*]);
    //Beware this is a private info !
    self::notify($player,'giveCardTo', '', [
      'player' => $player,
    */
      'card' => $card->getUiData(),
    ]);
  }

  /**
   * @param Player $player
   * @param int $color
   */
  public static function collectReserve($player, $color)
  {
    self::notifyAll('collectReserve', clienttranslate('${player_name} collects reserve cards of color ${color}'), [
      'player' => $player,
      'color' => $color,//TODO JSA COLOR NAME
    ]);
  }

  /**
   * @param Player $player
   * @param Collection $cards
   */
  public static function drawCards($player,$cards)
  {
    self::notifyAll('drawCards', clienttranslate('${player_name} draws ${n} cards from the deck'), [
      'player' => $player,
      'n' => $cards->count(),
      'cards' => $cards->ui(),
    ]);
  }
  /**
   * @param Player $player
   * @param Collection $cards
   */
  public static function discardCards($player,$cards)
  {
    self::notifyAll('discardCards', clienttranslate('${player_name} discard ${n} cards'), [
      'player' => $player,
      'n' => $cards->count(),
      'cards' => $cards->ui(),
    ]);
  }
  
  /**
   * @param Player $player
   */
  public static function pass($player)
  {
    self::notifyAll('pass', clienttranslate('${player_name} passes'), [
      'player' => $player,
    ]);
  }

  /**
   * @param Player $player
   * @param MasteryTile $tile
   */
  public static function takeTile($player,$tile)
  {
    self::notifyAll('takeTile', clienttranslate('${player_name} takes a mastery tile from the board'), [
      'player' => $player,
      'tile' => $tile->getUiData(),
    ]);
  }
  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  /**
   *  Empty notif to send after an action, to let framework works & refresh ui
   * (Usually not needed if we send another notif or if we change state of a player)
   * */
  public static function emptyNotif(){
    self::notifyAll('e','',[],);
  }
  /*********************
   **** UPDATE ARGS ****
   *********************/

  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      //for playername_wrapper
      $data['player_color'] = $data['player']->getColor();
      if (!isset($data['preserve'])) {
        $data['preserve'] = [];
      }
      $data['preserve'][] = 'player_color';

      unset($data['player']);
    }
    /* not used in this game for now
    if (isset($data['player2'])) {
      $data['player_name2'] = $data['player2']->getName();
      $data['player_id2'] = $data['player2']->getId();
      unset($data['player2']);
    }
    
    if (isset($data['player3'])) {
      $data['player_name3'] = $data['player3']->getName();
      $data['player_id3'] = $data['player3']->getId();
      unset($data['player3']);
    }
    */
  }
  
  /************************************
   **** UPDATES after confirm/undo ****
   ***********************************/
  
  public static function refreshUI($datas)
  {
    // Keep only the things from getAllDatas that matters
    $players = $datas['players'];
    $gameDatas = [
      'players' => $datas['players'],
      'cards' => $datas['cards'],
      'deckSize' => $datas['deckSize'],
      'tiles' => $datas['tiles'],
      'tokens' => $datas['tokens'],
    ];

    self::notifyAll('refreshUI', '', [
      'datas' => $gameDatas,
    ]);
  }
  
  /**
   * @param Player $player
   * @param array $notifIds
   */
  public static function clearTurn($player, $notifIds)
  {
    self::notifyAll('clearTurn', '', [
      'player' => $player,
      'notifIds' => $notifIds,
    ]);
  }
  
  /**
   * @param Player $player
   * @param int $stepId
   */
  public static function undoStep($player, $stepId)
  {
    self::notifyAll('undoStep', clienttranslate('${player_name} undoes their action'), [
      'player' => $player,
    ]);
  }
  /**
   * @param Player $player
   */
  public static function restartTurn($player)
  {
    self::notifyAll('restartTurn', clienttranslate('${player_name} restarts their turn'), [
      'player' => $player,
    ]);
  }

}
