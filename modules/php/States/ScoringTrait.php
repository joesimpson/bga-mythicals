<?php

namespace Bga\Games\Mythicals\States;

use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;
use Globals;

trait ScoringTrait
{
   
  //FOR TESTING PURPOSE
  public function stPreEndOfGame()
  {
    self::trace("stPreEndOfGame()");
    Notifications::emptyNotif();
    $this->gamestate->nextState('next');
  }

  public function stScoring()
  {
    self::trace("stScoring()");

    $players = Players::getAll();
    $this->computeFinalScore($players);

    $this->gamestate->nextState('next');
  }
  
  public function computeFinalScore($players)
  {
    self::trace("computeFinalScore()");
    Notifications::computeFinalScore();

    foreach($players as $pid => $player){
      $nbTokens = $player->getNbTokens();
      $scoreTokens = $nbTokens * TOKEN_SCORE;
      $scoreTiles = 0;
      $playerTiles = Tiles::getPlayerHand($pid);
      foreach($playerTiles as $tile){
        $scoreTiles += $tile->getScore();
      }

      $totalScore = $scoreTokens + $scoreTiles;
      $player->setScore($totalScore);
      Notifications::addPoints($player,$scoreTokens,clienttranslate('${player_name} scores ${n} ${points} with ${n2} tokens'), $nbTokens);
      Notifications::addPoints($player,$scoreTiles,clienttranslate('${player_name} scores ${n} ${points} with ${n2} tiles'), $playerTiles->count());
    }
    
  }

}
