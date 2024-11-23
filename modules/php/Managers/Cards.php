<?php

namespace Bga\Games\Mythicals\Managers;

use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Helpers\Utils;
use Bga\Games\Mythicals\Models\Card;
use Bga\Games\Mythicals\Models\CreatureCard;
use Bga\Games\Mythicals\Models\Player;

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
    //$privateCards = self::getPlayerHand($currentPlayerId);

    return 
      self::getInLocation(CARD_LOCATION_RESERVE)
      //->merge($privateCards)
      ->merge(self::getInLocation(CARD_LOCATION_CURRENT_DRAW))
      ->merge(self::getInLocation(CARD_LOCATION_HAND))
      ->merge(self::getInLocation(CARD_LOCATION_END))
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
  public static function setupDrawCardsToHand(Player $player,int $nbCards)
  {
    Game::get()->trace("setupDrawCardsToHand($nbCards)");
    $cards = new Collection();
    $colors = [];
    while(count($colors)< $nbCards){
      $card = self::pickForLocation(1,CARD_LOCATION_DECK, CARD_LOCATION_HAND)->first();
      //DRAW another until we have 2 different colors,
      // + check not DAY CARD 
      if(CARD_TYPE_DAY_CARD == $card->getType() || in_array( $card->getColor(),$colors) ){
        self::insertAtBottom($card->getId(), CARD_LOCATION_DECK);
        continue;
      }
      $colors[] = $card->getColor();
      $cards->append($card);
    }
    foreach($cards as $card){
      $card->setPId($player->getId());
      Notifications::giveCardTo($player,$card);
      Stats::inc("cards",$player->getId());
    }
    $missingNb = $nbCards - $cards->count();
    Game::get()->trace("setupDrawCardsToHand($nbCards) -> $missingNb are missing");
    if($missingNb>0) // Notifications::missingCards($player,$missingNb);
    return $missingNb;
  }
  
  /**
   * @param Player $player
   * @param int $nbCards
   * @return Collection
   */
  public static function drawCardsToSelection(Player $player,int $nbCards): Collection
  {
    Game::get()->trace("drawCardsToSelection($nbCards)");
    $cards = self::pickForLocation($nbCards, CARD_LOCATION_DECK, CARD_LOCATION_CURRENT_DRAW,0,false);
    foreach($cards as $card){
      //Notifications::giveCardTo($player,$card);
    }
    $missingNb = $nbCards - $cards->count();
    if($missingNb>0) Game::get()->trace("drawCardsToSelection($nbCards) -> $missingNb are missing");
    return $cards;
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
    //self::move($cards->getIds(),CARD_LOCATION_HAND);
    foreach($cards as $card){
      $card->setPId($player->getId());
      $card->setLocation(CARD_LOCATION_HAND);
      Notifications::giveCardTo($player,$card);
      Stats::inc("cards",$player);
    }
    return $cards;
  }
  
  public static function moveDuplicatesToOpponent(Player $player)
  {
    Game::get()->trace("moveDuplicatesToOpponent()");
    //HERE we suppose it is always a 2P game !
    $opponentPlayerId = Players::getNextId($player);
    $opponentPlayer = Players::get($opponentPlayerId);
    $cardsToMove = self::listDuplicatesInPlayerHand($player);
    foreach($cardsToMove as $card){
      $card->setPId($opponentPlayer->getId());
      $card->setLocation(CARD_LOCATION_HAND);
      Notifications::giveCardTo($opponentPlayer,$card,$player);
      Stats::inc("cards",$player,-1);
      Stats::inc("cards",$opponentPlayer,+1);
    }
    //return $cardsToMove;
  }
  
  /**
   * @return Collection cards 
   */
  public static function listDuplicatesInPlayerHand($player)
  {
    Game::get()->trace("listDuplicatesInPlayerHand()");
    $cardsInHand = Cards::getPlayerHand($player->getId());

    $cardsDuplicates = new Collection();
    $cardIdsDups = [];
    // $cardsInHand->map(function ($card) {
    //     return $cardsToMove[$card->getValue()][] = $card->getId() ;
    //   });
      
      
    foreach(CARD_COLORS as $color){
      $coloredCards = $cardsInHand->filter(function ($card) use ($color) {
        return $color == $card->getColor() ;
      });
      $values = $coloredCards->map(function ($card) {
        return $card->getValue();
      });
      foreach($values as $cardValue){
        $duplicatesForValue = $coloredCards->filter(function ($card) use ($cardValue) {
          return $cardValue == $card->getValue() ;
        })->getIds();
        if(count($duplicatesForValue) >= 2){
          $cardIdsDups[] = $duplicatesForValue[0];
        }
      }
    }
    $cardIdsDups = array_unique($cardIdsDups);
    foreach($cardsInHand as $card){
      if( in_array($card->getId(), $cardIdsDups) ){
        $cardsDuplicates->append($card);
      }
    }
    return $cardsDuplicates;
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

  
  /**
   * @param Collection $cards
   * @param int $length
   * @param bool $keepJokerApart (default false) : true if we want to find a SUite where Joker is another value and NOT a wildcard
   * @return array list of existing suits of specified $length in $cards
   */
  public static function listExistingSuites(Collection $cards, int $length, bool $keepJokerApart = false): array
  {

    $allSuites = [];
    for($i=CARD_VALUE_MIN; $i<=CARD_VALUE_MAX; $i++){
      $j = $i + $length -1;
      if($j<=CARD_VALUE_MAX){
      //for($j=$i+1; $j<=CARD_VALUE_MAX && $j<$i+$length; $j++){
        $currentSuit = Cards::findExistingSuites($cards, $i, $j, $keepJokerApart);
        if(isset($currentSuit) && count($currentSuit)>=$length ) $allSuites[] = $currentSuit;

        if($keepJokerApart) continue;

        for($k=$i; $k<=$j; $k++){//for each possible value in the suit, let's find a replacing joker
          $currentSuitWithForcedJoker = Cards::findExistingSuites($cards, $i, $j, false, $k);
          if(isset($currentSuitWithForcedJoker) && count($currentSuitWithForcedJoker)>=$length ) $allSuites[] = $currentSuitWithForcedJoker;
        }

      } 
    }
    

    //REMOVE DUPLICATES 
    $allSuites = Utils::array_of_uniquearrays($allSuites);
    return $allSuites;
  }

  
  /**
   * @param Collection $cards
   * @param int $fromValue
   * @param int $toValue
   * @param bool $keepJokerApart (default false) : true if we want to find a SUite where Joker is another value and NOT a wildcard
   * @param int $forceJoker (default null): a value if we want to find existing suite with a specified Joker value
   * @return array EXACT list of CARDS IDS representing a suite from $fromValue to $toValue,
   *    <br>EMPTY if not found
   */
  public static function findExistingSuites(
    Collection $cards, 
    int $fromValue, 
    int $toValue, 
    bool $keepJokerApart = false, 
    int $forceJoker = null
  ): array
  {
    $currentSuit = [];
    $jokerCard = null;
    $values = [];
    foreach($cards as $card ){
      $card_id = $card->getId();
      $value = $card->getValue();
      
      if(!array_key_exists($value,$values)){
        $values[$value] = $card_id;
      }
      if(CARD_VALUE_JOKER == $value) $jokerCard = $card_id;
    }
    
    if($keepJokerApart) {
      if(!isset($jokerCard)) {
        return [];//KO
      }
      else {
        $currentSuit[] = $jokerCard;
      }
    }

    for($i=$fromValue; $i<=$toValue; $i++){
      if(isset($forceJoker) && isset($jokerCard) && $forceJoker == $i) $currentSuit[] = $jokerCard;
      else if(array_key_exists($i,$values) ) $currentSuit[] = $values[$i];
      else if(!isset($forceJoker) && isset($jokerCard) && !in_array($jokerCard,$currentSuit)){
        $currentSuit[] = $jokerCard;
      }
      else {//KO
        return [];
      }
    }
    sort($currentSuit);

    return $currentSuit;
  }

  public static function getByType(int $cardType)
  {
    return self::DB()
      ->where('type', $cardType)
      ->get();
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
      self::setupDrawCardsToHand($player, NB_CARDS_PER_PLAYER);
    }

    // SHUFFLE  8 Cards + DAY CARD and add them to the bottom of the deck
    $dayCard = self::getByType(CARD_TYPE_DAY_CARD)->first();
    $dayCard->setLocation(CARD_LOCATION_CURRENT_SETUP);
    $cardsToShuffle = self::pickForLocation(NB_CARDS_SETUP_DAY_CARD, CARD_LOCATION_DECK, CARD_LOCATION_CURRENT_SETUP,0,false);
    self::shuffle(CARD_LOCATION_CURRENT_SETUP);
    $cardsToShuffle = self::getInLocation(CARD_LOCATION_CURRENT_SETUP);
    $cardsToShuffle = $cardsToShuffle->shuffle();
    self::shuffle(CARD_LOCATION_DECK);
    foreach ($cardsToShuffle as $card) {
      self::insertAtBottom($card->getId(), CARD_LOCATION_DECK);
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
      CARD_TYPE_DAY_CARD => $f([1,    CARD_COLOR_DAY, CARD_VALUE_1]), 
      
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
