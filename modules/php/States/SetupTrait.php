<?php

namespace Bga\Games\Mythicals\States;

use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;
use Bga\Games\Mythicals\Managers\Tokens;

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

    // Activate first player (which is in general a good idea :) )
    if(!array_key_exists("DEBUG",$options)){
      $this->activeNextPlayer();
    }
    /************ End of the game initialization *****/
  }
 
}
