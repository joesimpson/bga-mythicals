<?php

namespace Bga\Games\Mythicals\States;

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
    $possibleTiles = Tiles::getInLocation(TILE_LOCATION_BOARD);
    //TODO JSA Filter tiles based on rules
    $args = [
      "possibleTiles" => $possibleTiles->getIds(),
    ];
    
    $this->addArgsForUndo($args);
    return $args;
  }
  
  /**
   * Step 2.1 : player may choose a tile
   * @throws \BgaUserException
   */
  public function actTileChoice(int $tile_id): void
  {
    self::trace("actTileChoice($tile_id)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argTileChoice();
    $possibleTiles = $args['possibleTiles'];
    if (!in_array($tile_id, $possibleTiles)) {
      throw new UnexpectedException(101,"Invalid tile $tile_id ( see ".json_encode($possibleTiles).")");
    }

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
 
}
