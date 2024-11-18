<?php

namespace Bga\Games\Mythicals\Managers;

use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Models\Card;
use Bga\Games\Mythicals\Models\CreatureCard;

/* Class to manage all the cards */

class Cards extends \Bga\Games\Mythicals\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type'];
  protected static $autoreshuffle = true;
  protected static $autoreshuffleCustom = [CARD_LOCATION_DECK => CARD_LOCATION_DISCARD];

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $data = self::getCreaturesCardsTypes()[$type];
    return new CreatureCard($row, $data);
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all cards visible by this player
   */
  public static function getUiData($currentPlayerId)
  {
    $privateCards = self::getPlayerHand($currentPlayerId);

    return 
      self::getInLocation(CARD_LOCATION_DECK) //TODO JSA FILTER CARDS self::getInLocation(CARD_LOCATION_RESERVE)
      //->merge($privateCards)
      ->merge(self::getInLocation(CARD_LOCATION_HAND))
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toArray();
  } 
 
  /**
   * @param int $pId
   * @param string $location (optional)
   * @return int number of ALL CARDS owned by that player and in that $location,
   *   or ALL CARDS owned by that player if location not given
   */
  public static function countPlayerCards($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  
  /**
   * Return all HAND cards of this player
   * @param int $pId
   * @return Collection
   */
  public static function getPlayerHand($pId)
  {
    return self::getFilteredQuery($pId, CARD_LOCATION_HAND)->get();
  }

  /**
   * @param Player $player
   * @param int $nbCards
   * @return int $missingNb number of expected cards we cannot draw
   */
  public static function drawCardsToHand($player,$nbCards)
  {
    Game::get()->trace("drawCardsToHand($nbCards)");
    $cards = self::pickForLocation($nbCards, CARD_LOCATION_DECK, CARD_LOCATION_HAND,0,true);
    foreach($cards as $card){
      $card->setPId($player->getId());
      Notifications::giveCardTo($player,$card);
    }
    $missingNb = $nbCards - $cards->count();
    Game::get()->trace("drawCardsToHand($nbCards) -> $missingNb are missing");
    if($missingNb>0) // Notifications::missingCards($player,$missingNb);
    return $missingNb;
  }

  /**
   * @param Player $player
   * @param int $color
   * @return Collection $cards
   */
  public static function moveReserveToPlayer($player,$color)
  {
    Game::get()->trace("moveReserveToPlayer($color)");
    $cards = self::getInLocation(CARD_LOCATION_RESERVE)
      ->filter(function ($card) use ($color) {
        return $color == $card->getColor();
      });
    self::move($cards->getIds(),CARD_LOCATION_HAND);
    foreach($cards as $card){
      $card->setPId($player->getId());
      Notifications::giveCardTo($player,$card);
    }
    return $cards;
  }
  
  /**
   * @return array 
   */
  public static function listReserveColors()
  {
    Game::get()->trace("listReserveColors()");
    $colors = self::getInLocation(CARD_LOCATION_RESERVE)
      ->map(function ($card) {
        return $card->getColor();
      })->toUniqueArray();
    return $colors;
  }
  ///////////////////////////////////////////////////////////////////////////////////////
   
  /** Creation of the cards
   * @param Collection $players
   */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getCreaturesCardsTypes() as $type => $card) {
      $cards[] = [
        'location' => CARD_LOCATION_DECK,
        'type' => $type,
        'nbr' => $card['nbr'],
      ];
    } 

    self::create($cards);
    self::shuffle(CARD_LOCATION_DECK);

    foreach($players as $player){
      self::drawCardsToHand($player, NB_CARDS_PER_PLAYER);
    }
  }
  
  /**
   * @return array of all the different types of Creatures Cards
   */
  public static function getCreaturesCardsTypes()
  {
    $f = function ($t) {
      return [
        'nbr' => $t[0],
        'color' => $t[1],
        'value' => $t[2],
      ];
    };
    return [
      1 => $f([ NB_CREATURE_COPIES, CARD_COLOR_BLUE, CARD_VALUE_1]), 
      2 => $f([ NB_CREATURE_COPIES, CARD_COLOR_BLUE, CARD_VALUE_2]), 
      3 => $f([ NB_CREATURE_COPIES, CARD_COLOR_BLUE, CARD_VALUE_3]), 
      4 => $f([ NB_CREATURE_COPIES, CARD_COLOR_BLUE, CARD_VALUE_4]), 
      5 => $f([ NB_CREATURE_COPIES, CARD_COLOR_BLUE, CARD_VALUE_5]), 
      6 => $f([ NB_CREATURE_COPIES, CARD_COLOR_BLUE, CARD_VALUE_JOKER]), 

      //The day card will be unique
      7 => $f([1,    CARD_COLOR_DAY, CARD_VALUE_1]), 
      
      8 => $f([ NB_CREATURE_COPIES, CARD_COLOR_GREEN, CARD_VALUE_1]), 
      9 => $f([ NB_CREATURE_COPIES, CARD_COLOR_GREEN, CARD_VALUE_2]), 
      10 => $f([NB_CREATURE_COPIES, CARD_COLOR_GREEN, CARD_VALUE_3]), 
      11 => $f([NB_CREATURE_COPIES, CARD_COLOR_GREEN, CARD_VALUE_4]), 
      12 => $f([NB_CREATURE_COPIES, CARD_COLOR_GREEN, CARD_VALUE_5]), 
      13 => $f([NB_CREATURE_COPIES, CARD_COLOR_GREEN, CARD_VALUE_JOKER]), 

      15 => $f([NB_CREATURE_COPIES, CARD_COLOR_PURPLE, CARD_VALUE_1]), 
      16 => $f([NB_CREATURE_COPIES, CARD_COLOR_PURPLE, CARD_VALUE_2]), 
      17 => $f([NB_CREATURE_COPIES, CARD_COLOR_PURPLE, CARD_VALUE_3]), 
      18 => $f([NB_CREATURE_COPIES, CARD_COLOR_PURPLE, CARD_VALUE_4]), 
      19 => $f([NB_CREATURE_COPIES, CARD_COLOR_PURPLE, CARD_VALUE_5]), 
      20 => $f([NB_CREATURE_COPIES, CARD_COLOR_PURPLE, CARD_VALUE_JOKER]), 

      22 => $f([NB_CREATURE_COPIES, CARD_COLOR_RED, CARD_VALUE_1]), 
      23 => $f([NB_CREATURE_COPIES, CARD_COLOR_RED, CARD_VALUE_2]), 
      24 => $f([NB_CREATURE_COPIES, CARD_COLOR_RED, CARD_VALUE_3]), 
      25 => $f([NB_CREATURE_COPIES, CARD_COLOR_RED, CARD_VALUE_4]), 
      26 => $f([NB_CREATURE_COPIES, CARD_COLOR_RED, CARD_VALUE_5]), 
      27 => $f([NB_CREATURE_COPIES, CARD_COLOR_RED, CARD_VALUE_JOKER]), 
      
    
    ];
  }
}
