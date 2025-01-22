<?php

namespace Bga\Games\MythicalsTheBoardGame\States;

use Bga\Games\MythicalsTheBoardGame\Core\Globals;
use Bga\Games\MythicalsTheBoardGame\Core\Stats;
use Bga\Games\MythicalsTheBoardGame\Managers\Cards;
use Bga\Games\MythicalsTheBoardGame\Managers\Players;
use Bga\Games\MythicalsTheBoardGame\Managers\Tiles;
use Bga\Games\MythicalsTheBoardGame\Managers\Tokens;

trait SetupTrait
{
  
  /*
      setupNewGame:
      
      This method is called only once, when a new game is launched.
      In this method, you must setup the game according to the game rules, so that
      the game is ready to be played.
  */
  protected function setupNewGame($players, $options = [])
  {
    Globals::setupNewGame($players, $options);
    $playersDatas = Players::setupNewGame($players, $options);
    Stats::setupNewGame($playersDatas);
    Cards::setupNewGame($playersDatas,$options);
    Tiles::setupNewGame($players,$options);
    Tokens::setupNewGame($players,[]);

    $this->setGameStateInitialValue('logging', true); 

    /************ End of the game initialization *****/
  }
 
}
