<?php

namespace Bga\Games\Mythicals\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;

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
  public function actTileChoice(int $tile_id, #[IntArrayParam] array $card_ids): void
  {
    self::trace("actTileChoice($tile_id)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argTileChoice();
    $possibleTiles = $args['possibleTiles'];
    if(! array_key_exists($tile_id, $possibleTiles)){
      throw new UnexpectedException(101,"Invalid tile $tile_id ( see ".json_encode($possibleTiles).")");
    }
    $expectedDatas = $possibleTiles[$tile_id];
    $nbExpectedCards = $expectedDatas['n'];
    $possibleCardIds = $expectedDatas['c'];
    if ($nbExpectedCards != count($card_ids)) {
      throw new UnexpectedException(102,"You must select $nbExpectedCards cards");
    }
    foreach($card_ids as $paramCardId){
      if (!in_array($paramCardId, $possibleCardIds)) {
        throw new UnexpectedException(103,"Invalid card $paramCardId ( see ".json_encode($possibleCardIds).")");
      }
    }

    //TODO JSA RULE MESSAGE WHEN CARDS ARE NOT REPRESENTING A VALID SET (suite/same)

    //  game logic here. 
    $tile = Tiles::get($tile_id);
    $cards = Cards::getMany($card_ids);
    $tile->setPId($pId);
    $tile->setLocation(TILE_LOCATION_HAND);
    foreach($cards as $card){
      $card->setLocation(CARD_LOCATION_DISCARD);
      $card->setPId(null);
    }
    Notifications::discardCards($player,$cards);
    Notifications::takeTile($player,$tile);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }
 
  public function actPass(): void
  {
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

    foreach($possibleTiles as $tileId => $tile){

      $tileScoringType = $tile->getBoardPosition();
      $tileColor = $tile->getColor();
      $cardsOfTileColor = $playerCards->filter(
          function($card) use ($tileColor) { 
            return $tileColor == $card->getColor();
        });

      $nbExpectedCards = 1;
      $expectedSameColor = false;
      $expectedSameValue = false;
      $possibleCards = [];

      switch($tileScoringType){
        //------------------------------
        case TILE_SCORING_SUITE_2:
          $nbExpectedCards = 2;
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_3:
          $nbExpectedCards = 3;
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_4:
          $nbExpectedCards = 4;
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        case TILE_SCORING_SUITE_5:
          $nbExpectedCards = 5;
          $expectedSameColor = true;
          $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
          break;
        //------------------------------
        case TILE_SCORING_SAME_2:
          $nbExpectedCards = 2;
          $expectedSameValue = true;
          break;
        case TILE_SCORING_SAME_3:
          $nbExpectedCards = 3;
          $expectedSameValue = true;
          break;
        case TILE_SCORING_SAME_4:
          $nbExpectedCards = 4;
          $expectedSameValue = true;
          break;
        case TILE_SCORING_SAME_6:
          $nbExpectedCards = 6;
          $expectedSameValue = true;
          break;
        //------------------------------
        default: break;
      }

      if(!empty($possibleCards)){
        $possibleCardsIds = $possibleCards[0];//TODO JSA GET ALL in array
        $tiles_datas[$tileId] = [
          //nbExpectedCards:
          'n' => $nbExpectedCards,
          //cardIds :
          'c' => $possibleCardsIds,
        ];
      }
    }
    return $tiles_datas;
  }
}
