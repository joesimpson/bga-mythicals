<?php

namespace Bga\Games\MythicalsTheBoardGame\States;

trait EndTurnTrait
{
   
  public function stEndTurn()
  { 
    self::trace("stEndTurn()");

    //$activePlayer = Players::getActive();
    //Notifications::endTurn($turnPlayer);
    
    $this->addCheckpoint(ST_END_TURN);
    $this->addCheckpoint(ST_NEXT_TURN);
    $this->gamestate->nextState('next');
  }
  
  
}
