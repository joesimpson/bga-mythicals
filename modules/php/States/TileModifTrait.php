<?php

namespace Bga\Games\Mythicals\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Game;
use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;

trait TileModifTrait
{
  
  /**
   * Game state arguments, example content.
   *
   * This method returns some additional information that is very specific to the `tileModif` game state.
   *
   * @return array
   * @see ./states.inc.php
   */
  public function argTileModif(): array
  {
    $possibleTiles = Tiles::getInLocation(TILE_LOCATION_BOARD.'%');
    $possibleTilesToReinforce = $possibleTiles->filter(function($tile){
      $tokens = $tile->getTokens();
      return TILE_FACE_OPEN == $tile->getFace() && count($tokens)< NB_MAX_TOKENS_ON_TILE;
    });
    //TODO JSA CHECK remaining Tokens
    $possibleTilesToLock = $possibleTiles->filter(function($tile){
      $tokens = $tile->getTokens();
      return TILE_FACE_OPEN == $tile->getFace() && 0 == count($tokens);
    });
    $args = [
      "tiles_ids_r" => $possibleTilesToReinforce->getIds(),
      "tiles_ids_l" => $possibleTilesToLock->getIds(),
    ];
    
    $this->addArgsForUndo($args);
    return $args;
  }
  
  /**
   * Step 2.2 : player may REINFORCE a tile
   * @param int $tile_id : chosen tile 
   * @param int $nTokens : nb of tokens to add
   * @throws \BgaUserException
   */
  public function actTileReinforce(int $tile_id, int $nTokens,#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actTileReinforce($tile_id,$nTokens)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argTileModif();
    $possibleTiles = $args['tiles_ids_r'];
    if (!in_array($tile_id, $possibleTiles)) {
      throw new UnexpectedException(110,"Invalid tile $tile_id ( see ".json_encode($possibleTiles).")");
    }
    //TODO JSA CHECK nTokens

    //  game logic here. 
    $tile = Tiles::get($tile_id);
    Notifications::reinforceTile($player,$tile,$nTokens);
    $tokens = $tile->addBonus($nTokens);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }
  /**
   * Step 2.3 : player may LOCK a tile
   * @throws \BgaUserException
   */
  public function actTileLock(int $tile_id,#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actTileLock($tile_id)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argTileModif();
    $possibleTiles = $args['tiles_ids_l'];
    if (!in_array($tile_id, $possibleTiles)) {
      throw new UnexpectedException(106,"Invalid tile $tile_id ( see ".json_encode($possibleTiles).")");
    }

    //  game logic here. 
    $tile = Tiles::get($tile_id);
    $tile->setFace(TILE_FACE_LOCKED);
    Notifications::lockTile($player,$tile);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }
}
