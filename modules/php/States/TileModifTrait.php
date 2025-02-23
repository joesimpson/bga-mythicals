<?php

namespace Bga\Games\MythicalsTheBoardGame\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\Games\MythicalsTheBoardGame\Core\Globals;
use Bga\Games\MythicalsTheBoardGame\Core\Notifications;
use Bga\Games\MythicalsTheBoardGame\Core\Stats;
use Bga\Games\MythicalsTheBoardGame\Exceptions\UnexpectedException;
use Bga\Games\MythicalsTheBoardGame\Game;
use Bga\Games\MythicalsTheBoardGame\Managers\Cards;
use Bga\Games\MythicalsTheBoardGame\Managers\Players;
use Bga\Games\MythicalsTheBoardGame\Managers\Tiles;
use Bga\Games\MythicalsTheBoardGame\Managers\Tokens;

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
    $nbAvailableTokens = Tokens::countInLocation(TOKEN_LOCATION_BOARD);
    $possibleTilesToReinforce = $possibleTiles->filter(function($tile) use ($nbAvailableTokens){
      return TILE_FACE_OPEN == $tile->getFace() && $tile->getNbEmptyTokenSpots()>0 && $nbAvailableTokens>0;
    })->map(function($tile) use ($nbAvailableTokens){
      return min($nbAvailableTokens,$tile->getNbEmptyTokenSpots());
    });
    $possibleTilesToLock = $possibleTiles->filter(function($tile){
      $tokens = $tile->getTokens();
      return TILE_FACE_OPEN == $tile->getFace() && 0 == count($tokens);
    });
    $args = [
      "tiles_ids_r" => $possibleTilesToReinforce,
      "tiles_ids_l" => $possibleTilesToLock->getIds(),
      
      //AUTO SKIP STATE when END GAME TRIGGER
      //'_no_notify' => Cards::countInLocation(CARD_LOCATION_END) > 0,
    ];
    
    $this->addArgsForUndo($args);
    return $args;
  }
  
  public function stTileModif(): void{
    //$args = $this->argTileModif();
    //if ($args['_no_notify']) {
    //Publisher request : AUTO SKIP This useless step when END GAME TRIGGER
    if(Cards::countInLocation(CARD_LOCATION_END) > 0){
      $this->gamestate->nextState('pass');
      return;
    }
  }
  /**
   * Step 2.2 : player may REINFORCE a tile
   * @param int $tile_id : chosen tile 
   * @param int $nTokens : nb of tokens to add
   * @throws \BgaUserException
   */
  public function actTileReinforce(int $tile_id, #[IntParam(min: '1')] int $nTokens,#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actTileReinforce($tile_id,$nTokens)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argTileModif();
    $possibleTiles = $args['tiles_ids_r'];
    $possibleTilesIds = $possibleTiles->getIds();
    if (!in_array($tile_id, $possibleTilesIds)) {
      throw new UnexpectedException(110,"Invalid tile $tile_id ( see ".json_encode($possibleTilesIds).")");
    }

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
