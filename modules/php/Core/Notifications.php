<?php

namespace Bga\Games\MythicalsTheBoardGame\Core;

use Bga\Games\MythicalsTheBoardGame\Game;
use Bga\Games\MythicalsTheBoardGame\Helpers\Collection;
use Bga\Games\MythicalsTheBoardGame\Helpers\Utils;
use Bga\Games\MythicalsTheBoardGame\Managers\Cards;
use Bga\Games\MythicalsTheBoardGame\Managers\Tiles;
use Bga\Games\MythicalsTheBoardGame\Models\Card;
use Bga\Games\MythicalsTheBoardGame\Models\MasteryTile;
use Bga\Games\MythicalsTheBoardGame\Models\Player;
use Bga\Games\MythicalsTheBoardGame\Models\Token;

class Notifications
{ 
  
  // Commented because unused in this game, and we don't need to translate that message
  ///**
  // * @param Player $player
  // * @param int $points
  // * @param string $msg (optional) Message to overwrite default
  // */
  //public static function addPoints(Player $player,int $points, string $msg = null, int $n2 =null){
  //  if(!isset($msg)) $msg = clienttranslate('${player_name} scores ${n} points');
  //  $args = [ 
  //    'player' => $player,
  //    'n' => $points,
  //  ];
  //  if(isset($n2)) $args['n2'] = $n2;
  //  self::notifyAll('addPoints',$msg, $args, );
  //}

  /**
   */
  public static function computeFinalScore()
  {
    self::notifyAll('computeFinalScore', clienttranslate('Computing final score...'), [
    ]);
  }
 
  public static function scoreTile(Player $player,MasteryTile $tile, int $points){
    $msg = clienttranslate('${player_name} scores ${n} points with a ${tile_color} tile');
    $args = [ 
      'player' => $player,
      'n' => $points,
      'tile' => $tile->getUiData(),

      'i18n' => ['tile_color'],  
      'tile_color' => Tiles::getColorName($tile->getColor()),
      'preserve' => [ 'tile_color_type' ],
      'tile_color_type' => $tile->getColor(),
    ];
    self::notifyAll('scoreTile',$msg, $args, );
  }
  
  public static function scoreTokens(Player $player, int $points, int $nbTokens){
    $msg = clienttranslate('${player_name} scores ${n} points with ${n2} tokens');
    $args = [ 
      'player' => $player,
      'n' => $points,
      'n2' => $nbTokens,
    ];
    self::notifyAll('scoreTokens',$msg, $args, );
  }

  ///**
  // * @param Player $player
  // * @param Card $card
  // * @deprecated see cardsToReserve
  // */
  //public static function cardToReserve(Player $player, $card)
  //{
  //  self::notifyAll('cardToReserve', clienttranslate('A new card goes to the reserve'), [
  //    //'player' => $player,
  //    'card' => $card->getUiData(),
  //  ]);
  //}
  
  /**
   * Same notif as cardToReserve() but send all cards at the same time to animate all at the same time
   * @param Player $player
   * @param Collection $cards
   */
  public static function cardsToReserve(Player $player, Collection $cards)
  {
    $msg = clienttranslate('${n} cards go to the reserve');
    self::notifyAll('cardsToReserve', $msg, [
      //'player' => $player,
      'cards' => $cards->ui(),
      'n' =>  $cards->count(),
    ]);
  }
  
  /**
   * @param Player $player
   * @param Card $card
   * @param Player $fromPlayer
   * @deprecated see giveCardsToPlayer to send a Collection 
   */
  public static function giveCardTo(Player $player, Card $card, Player $fromPlayer = null)
  {
    $msg = clienttranslate('${player_name} receives ${n} cards');
    if(isset($fromPlayer)) $msg = clienttranslate('${player_name} receives ${n} cards from ${player_name2}');
    //"giveCardToPublic" represents the public informations to notify (in case we want another with private infos)
    self::notifyAll('giveCardToPublic', $msg, [
      'player' => $player,
      'player2' => $fromPlayer,
      'card' => $card->getUiData(),
      'n' =>  1,
    ]);
    //self::giveCardsToPlayer($player, new Collection($card),$fromPlayer);
  }
  
  /**
   * Same notif as giveCardTo() but send all cards at the same time to animate all at the same time
   * @param Player $player
   * @param Collection $cards
   * @param Player $fromPlayer
   */
  public static function giveCardsToPlayer(Player $player, Collection $cards, Player $fromPlayer = null)
  {
    $msg = clienttranslate('${player_name} receives ${n} cards');
    if(isset($fromPlayer)) $msg = clienttranslate('${player_name} receives ${n} cards from ${player_name2}');
    self::notifyAll('giveCardsToPlayer', $msg, [
      'player' => $player,
      'player2' => $fromPlayer,
      'cards' => $cards->ui(),
      'n' =>  $cards->count(),
    ]);
  }

  /**
   * @param Player $player
   * @param Card $card
   */
  public static function dayCard(Player $player, Card $card)
  {
    $msg = clienttranslate('The day card appears, this is the last turn !');
    self::notifyAll('dayCard', $msg, [
      'player' => $player, 
      'card' => $card->getUiData(),
    ]);
  }
  /**
   * @param Player $player
   * @param int $color
   */
  public static function collectFromDeck(Player $player, int $color)
  {
    self::notifyAll('collectFromDeck', clienttranslate('${player_name} collects the drawn cards of ${card_color} color'), [
      'i18n' => ['card_color'],  
      'player' => $player,
      'card_color' => Cards::getColorName($color),
      'preserve' => [ 'card_color_type' ],
      'card_color_type' => $color,
    ]);
  }
  /**
   * @param Player $player
   * @param int $color
   */
  public static function collectReserve(Player $player,int $color)
  {
    self::notifyAll('collectReserve', clienttranslate('${player_name} collects reserve cards of ${card_color} color'), [
      'i18n' => ['card_color'],  
      'player' => $player,
      'card_color' => Cards::getColorName($color),
      'preserve' => [ 'card_color_type' ],
      'card_color_type' => $color,
    ]);
  }

  /**
   * @param Player $player
   * @param Collection $cards
   */
  public static function drawCards(Player $player,$cards)
  { 
    self::notifyAll('drawCards', clienttranslate('${player_name} draws ${n} cards from the deck'), [
      'player' => $player,
      'n' => $cards->count(),
      'cards' => $cards->ui(),
    ]);

    Notifications::cardsDetail($cards);
  }
  
  /**
   * @param Collection $cards
   */
  public static function cardsDetail(Collection $cards)
  {
    //prepare to log cards:
    $cards_logs = [];
    $cards_args = [];
    $k = 1;

    foreach ($cards as $card) {
      $name = 'card_color_' . $k;
      $value = 'card_value_' . $k;

      $cardValue = $card->getValue();
      if(CARD_VALUE_JOKER == $cardValue) $cardValue = "*";
      else if(CARD_TYPE_DAY_CARD == $card->getType()) {
        $cardValue = clienttranslate("Day Card");
        $cards_args['i18n'][] = $value;
      }

      $cards_logs[] = '${' . $value  . '} ${' . $name . '}';
      $cards_args[$value] = $cardValue;
      $cards_args[$name] = Cards::getColorName($card->getColor());
      $cards_args['i18n'][] = $name;
      $cards_args['preserve'][] = "card_colortype_$k";
      $cards_args["card_colortype_$k"] = $card->getColor();
      $k++;
    }

    $cards_names = [
      'log' => join(', ', $cards_logs),
      'args' => $cards_args,
    ];
    self::notifyAll('cardsDetail', ('${cards_names}'), [
      'cards_names' => $cards_names,
    ]);
  }
  /**
   * @param Player $player
   * @param Collection $cards
   */
  public static function discardCards(Player $player,$cards)
  {
    self::notifyAll('discardCards', clienttranslate('${player_name} discards ${n} cards'), [
      'player' => $player,
      'n' => $cards->count(),
      'cards' => $cards->ui(),
    ]);
  }
  
  /**
   * @param Player $player
   */
  public static function pass(Player $player)
  {
    self::notifyAll('pass', clienttranslate('${player_name} passes'), [
      'player' => $player,
    ]);
  }

  /**
   * @param Player $player
   * @param MasteryTile $tile
   */
  public static function takeTile(Player $player,MasteryTile $tile)
  {
    self::notifyAll('takeTile', clienttranslate('${player_name} takes a ${tile_color} mastery tile from the board'), [
      'player' => $player,
      'tile' => $tile->getUiData(),
      
      'i18n' => ['tile_color'],  
      'tile_color' => Tiles::getColorName($tile->getColor()),
      'preserve' => [ 'tile_color_type' ],
      'tile_color_type' => $tile->getColor(),
    ]);
  }
  /**
   * @param Player $player
   * @param MasteryTile $tile
   */
  public static function lockTile(Player $player,MasteryTile $tile)
  {
    self::notifyAll('lockTile', clienttranslate('${player_name} locks a mastery tile on the board'), [
      'player' => $player,
      'tile' => $tile->getUiData(),
    ]);
  }
  
  /**
   * @param Player $player
   * @param MasteryTile $tile
   * @param int $nbTokens
   */
  public static function reinforceTile(Player $player,MasteryTile $tile,int $nbTokens)
  {
    self::notifyAll('reinforceTile', clienttranslate('${player_name} reinforces a mastery tile on the board with ${n} ${token_name}'), [
      'player' => $player,
      'tile' => $tile->getUiData(),
      'n' => $nbTokens,
      'i18n' => ['token_name'],  
      'token_name' => clienttranslate("Bonus markers"),
      'preserve' => [ 'token_type' ],
      'token_type' => TOKEN_TYPE_BONUS_MARKER,
    ]);
  }
  
  /**
   * @deprecated use newBonusMarkersOnTile 
   */
  public static function newBonusMarkerOnTile(MasteryTile $tile,Token $token)
  {
    self::notifyAll('newBonusMarkerOnTile', '', [
      'tile_id' => $tile->getId(),
      'token' => $token->getUiData(),
    ]);
  }
  public static function newBonusMarkersOnTile(MasteryTile $tile,Collection $tokens)
  {
    self::notifyAll('newBonusMarkersOnTile', '', [
      'tile_id' => $tile->getId(),
      'tokens' => $tokens->ui(),
    ]);
  }
 
  public static function takeBonus(Player $player,Token $token)
  {
    self::notifyAll('takeBonus', clienttranslate('${player_name} receives 1 ${token_name}'), [
      'i18n' => ['token_name'],  
      'player' => $player,
      'token' => $token->getUiData(),
      'token_name' => clienttranslate("Bonus markers"),
      'preserve' => [ 'token_type' ],
      'token_type' => $token->getType(),
    ]);
  }
  
  /**
   * @deprecated see 1 by 1 revelation in scoreTile
   */
  public static function revealTiles(Player $player,Collection $tiles)
  {
    self::notifyAll('revealTiles', '', [
      'player' => $player,
      'tiles' => $tiles->ui(),
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
    if (isset($data['player2'])) {
      $data['player_name2'] = $data['player2']->getName();
      $data['player_id2'] = $data['player2']->getId();
      //for playername_wrapper
      $data['player_color2'] = $data['player2']->getColor();
      if (!isset($data['preserve'])) {
        $data['preserve'] = [];
      }
      $data['preserve'][] = 'player_color2';
      unset($data['player2']);
    }
    else {
      unset($data['player2']);
    }
    /* not used in this game for now
    
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
