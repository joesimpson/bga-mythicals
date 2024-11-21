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

trait PlayerTurnTrait
{
  
  /**
   * Game state arguments, example content.
   *
   * This method returns some additional information that is very specific to the `playerTurn` game state.
   *
   * @return array
   * @see ./states.inc.php
   */
  public function argPlayerTurn(): array
  {
    $drawnCards = Cards::getInLocation(CARD_LOCATION_CURRENT_DRAW);
    $possibleDraw = count($drawnCards) == 0 ;
    $args = [
      "d" => $possibleDraw,
      "drawnCards" => $drawnCards->ui(),
      //If we cannot draw, we have to choose in cards drawn
      "reserveColors" => $possibleDraw ? Cards::listReserveColors() : [],
    ];
    
    $this->addArgsForUndo($args);
    return $args;
  }
  
  /**
   * Step 1.1 : player can choose to draw 3 cards
   * @throws \BgaUserException
   */
  public function actDraw(#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actDraw()");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argPlayerTurn();
    if (!$args["d"]) {
      throw new UnexpectedException(1,'You cannot draw cards now');
    }

    //  game logic here.
    $cards = Cards::drawCardsToSelection($player,NB_CARDS_PER_DRAW);
    Notifications::drawCards($player,$cards);
    foreach($cards as $card){
      if($card->getColor() == CARD_COLOR_DAY){
        $card->setLocation(CARD_LOCATION_END);
        Notifications::dayCard($player,$card);
      }
    }

    // at the end of the action, move to the next state
    $this->gamestate->nextState("draw");
  }
  
  /**
   * Step 1.2 : player must choose a color in drawn cards
   * @throws \BgaUserException
   */
  public function actCollectDraw(int $color,#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actCollectDraw($color)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $drawnCards = Cards::getInLocation(CARD_LOCATION_CURRENT_DRAW);
    $drawnCardsColors = $drawnCards->map( function($card) { return $card->getColor();})->toArray();
    if (!in_array($color, $drawnCardsColors)) {
      throw new UnexpectedException(3,"Invalid color $color in drawn cards (".json_encode($drawnCardsColors).")");
    }

    //  game logic here.
    foreach($drawnCards as $card){
      if($card->getColor() == $color){
        $card->setPId($player->getId());
        $card->setLocation(CARD_LOCATION_HAND);
        Notifications::giveCardTo($player,$card);
      }
      else {
        //move others to RESERVE !
        $card->setPId(null);
        $card->setLocation(CARD_LOCATION_RESERVE);
        Notifications::cardToReserve($player,$card);

      }
    }
    Cards::moveDuplicatesToOpponent($player);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }

  /**
   * Step 1.3 : player can choose a color in reserve cards
   * @throws \BgaUserException
   */
  public function actCollectReserve(int $color,#[IntParam(name: 'v')] int $version,): void
  {
    Game::get()->checkVersion($version);
    self::trace("actCollectReserve($color)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argPlayerTurn();
    $reserveColors = $args['reserveColors'];
    if (!in_array($color, $reserveColors)) {
      throw new UnexpectedException(2,"Invalid color $color in reserve (".json_encode($reserveColors).")");
    }

    //  game logic here.
    Notifications::collectReserve($player,$color);
    $cards = Cards::moveReserveToPlayer($player,$color);
    Cards::moveDuplicatesToOpponent($player);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }
 
}
