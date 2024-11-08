<?php

namespace Bga\Games\Mythicals\States;

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

    // Give some extra time to the active player when he completed an action
    $this->giveExtraTime($player_id);
    
    $this->activeNextPlayer();

    $this->addCheckpoint(ST_PLAYER_TURN);
    // Go to another gamestate
    // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
    $this->gamestate->nextState("nextPlayer");
  }
}
