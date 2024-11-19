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

    //TODO JSA RULE MESSAGE WHEN CARDS ARE NOT REPRESENTING A RIGHT SET (suite/same)

    //  game logic here. 

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
   * @return array datas about tiles to take as : [tile_id => [nbExpectedCards as 'n',cardIds as 'c',]]
   */
  public function listPossibleTilesToTake($player): array
  {
    $possibleTiles = Tiles::getInLocation(TILE_LOCATION_BOARD.'%');

    foreach($possibleTiles as $tileId => $tile){

      $tiles_datas[$tileId] = [
        //nbExpectedCards:
        'n' => $tile->getColor() +1, //TODO JSA COMPUTE real value
        //cardIds :
        'c' => [1829,1832,1833,1834],//TODO JSA COMPUTE at player cards
      ];
    }
    return $tiles_datas;
  }
}
