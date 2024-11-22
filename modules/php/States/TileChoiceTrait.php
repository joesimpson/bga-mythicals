<?php

namespace Bga\Games\Mythicals\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Exceptions\UserException;
use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;
use Bga\Games\Mythicals\Models\MasteryTile;

trait TileChoiceTrait
{
  
  /**
   * Game state arguments, example content.
   *
   * This method returns some additional information that is very specific to the `tileChoice` game state.
   *
   * @return array
   * @see ./states.inc.php
   */
  public function argTileChoice(): array
  {
    $player = Players::getActive();
    $args = [
      "possibleTiles" => $this->listPossibleTilesToTake($player),
    ];
    
    $this->addArgsForUndo($args);
    return $args;
  }
  
  /**
   * Step 2.1 : player may choose a tile
   * @throws \BgaUserException
   */
  public function actTileChoice(int $tile_id, #[IntArrayParam] array $card_ids, #[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actTileChoice($tile_id)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $tile = Tiles::get($tile_id);
    $playerCards = Cards::getPlayerHand($player->getId());
    $possibleCardsIdsArrays = $this->listPossibleCardsToDiscardForTile($playerCards,$tile);
    if(empty($possibleCardsIdsArrays))
    {
      throw new UnexpectedException(101,"Invalid tile $tile_id");
    }
    $nbExpectedCards = $tile->getNbCardsToDiscard();
    if ($nbExpectedCards != count($card_ids)) {
      throw new UnexpectedException(102,"You must select $nbExpectedCards cards");
    }
    // RULE MESSAGE WHEN CARDS ARE NOT REPRESENTING A VALID SET (suite/same)
    $foundMatch = false;
    foreach($possibleCardsIdsArrays as $possibleCardsIdsArray){
      $diff = array_diff($card_ids, $possibleCardsIdsArray);
      if (count($diff) == 0){
        $foundMatch = true;
        break;
      }
    }
    if (!$foundMatch) {
      throw new UserException(103,"These cards are not a valid set to get this tile");
    }

    //  game logic here. 
    $cards = Cards::getMany($card_ids);
    $tile->setPId($pId);
    $tile->setLocation(TILE_LOCATION_HAND);
    Stats::inc("tiles",$player);
    Notifications::discardCards($player,$cards);
    foreach($cards as $card){
      $card->setLocation(CARD_LOCATION_DISCARD);
      $card->setPId(null);
      Stats::inc("cards",$player,-1);
    }
    Notifications::takeTile($player,$tile);
    $tokens = $tile->getTokens();
    foreach($tokens as $token){
      $token->setLocation(TOKEN_LOCATION_HAND);
      $token->setPId($pId);
      Notifications::takeBonus($player,$token);
      
      Stats::inc("bonus_token",$player);
    }

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }
 
  public function actPass(#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actPass()");
    $player = Players::getCurrent();
    $this->addStep();

    // Notify all players about the choice to pass.
    Notifications::pass($player);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("pass");
  }
 

  ////////////////////////////////////////////////////////////////////////////
  
  /**
   * @param Player $player
   * @return array datas about tiles to take by player as : [tile_id => [nbExpectedCards as 'n',cardIds as 'c',]]
   */
  public function listPossibleTilesToTake($player): array
  {
    $possibleTiles = Tiles::getInLocation(TILE_LOCATION_BOARD.'%');
    $playerCards = Cards::getPlayerHand($player->getId());
    $tiles_datas = [];

    foreach($possibleTiles as $tileId => $tile){
      
      $nbExpectedCards = $tile->getNbCardsToDiscard();
      $possibleCards = $this->listPossibleCardsToDiscardForTile($playerCards,$tile);

      if(!empty($possibleCards)){
        $possibleCardsIds = array_values(array_unique(array_merge([], ...$possibleCards)));
        if($nbExpectedCards <= count($possibleCardsIds)){
          $tiles_datas[$tileId] = [
            //NAME is short for JSON to send
            'n' => $nbExpectedCards,
            'c' => $possibleCardsIds,
          ];
        }
      }
    }
    return $tiles_datas;
  }

  /**
   * @param Collection $player
   * @param MasteryTile $tile
   * @return array arrays of cards ids as : [[1,2,3], [12,15,16],] ...
   */
  public function listPossibleCardsToDiscardForTile(Collection $playerCards,MasteryTile $tile): array
  {
      $tileScoringType = $tile->getBoardPosition();
      $tileColor = $tile->getColor();
      $cardsOfTileColor = $playerCards->filter(
          function($card) use ($tileColor) { 
            return $tileColor == $card->getColor();
        });

      $nbExpectedCards = $tile->getNbCardsToDiscard();
      $expectedSameColor = false;
      $expectedSameValue = false;
      $possibleCards = [];

      switch($tileScoringType){
        //------------------------------
        case TILE_SCORING_SUITE_2:
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_3:
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_4:
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_5:
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_6:
          $expectedSameColor = true;
          foreach(CARD_COLORS as $color){
            $cardsOfAnyColor = $playerCards->filter(
                function($card) use ($color) { 
                  return $color == $card->getColor();
              });
            $possibleCards = array_merge($possibleCards, Cards::listExistingSuites($cardsOfAnyColor, $nbExpectedCards-1,true));
          }
          break;
        //------------------------------
        case TILE_SCORING_SAME_2:
          $expectedSameValue = true;
          break;
        case TILE_SCORING_SAME_3:
          $expectedSameValue = true;
          break;
        case TILE_SCORING_SAME_4:
          $expectedSameValue = true;
          break;
        //------------------------------
        default: break;
      }
 
    return $possibleCards;
  }
}
