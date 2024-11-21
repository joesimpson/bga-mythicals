<?php

namespace Bga\Games\Mythicals\States;

use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;

trait NextTurnTrait
{
   
  /**
   * Game state action, example content.
   *
   * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
   */
  public function stNextPlayer(): void {
    // Retrieve the active player ID.
    $player_id = (int)$this->getActivePlayerId();


    if(Cards::countInLocation(CARD_LOCATION_END) > 0){
      //END GAME TRIGGER
      $this->addCheckpoint(ST_END_SCORING);
      $this->gamestate->nextState('end');
      return;
    }

    // Give some extra time to the active player when he completed an action
    $this->giveExtraTime($player_id);
    
    $this->activeNextPlayer();
    $player = Players::getActive();
    Players::setupNewTurn($player);

    $this->addCheckpoint(ST_PLAYER_TURN_COLLECT);
    // Go to another gamestate
    // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
    $this->gamestate->nextState("nextPlayer");
  }
}
