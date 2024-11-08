<?php

namespace Bga\Games\Mythicals\Managers;

use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Models\Card;

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
    $data = [];
    return new Card($row, $data);
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all cards visible by this player
   */
  public static function getUiData($currentPlayerId)
  {
    $privateCards = [];

    return 
      self::getAll() //TODO JSA FILTER CARDS self::getInLocation(CARD_LOCATION_D)
      ->merge($privateCards)
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
   
  /** Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getCreaturesCardsTypes() as $type => $card) {
      $cards[] = [
        'location' => CARD_LOCATION_DECK,
        'type' => $type,
      ];
    } 

    self::create($cards);
    self::shuffle(CARD_LOCATION_DECK);
  }
  
  /**
   * @return array of all the different types of Creatures Cards
   */
  public static function getCreaturesCardsTypes()
  {
    $f = function ($t) {
      return [
        'color' => $t[0],
      ];
    };
    return [
      //TODO JSA SETUP CARDS
      1 => $f(["blue",]), 
      2 => $f(["red",]), 
    
    ];
  }
}
