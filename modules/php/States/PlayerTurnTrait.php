<?php

namespace Bga\Games\Mythicals\States;

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
    return [
      "reserveColors" => Cards::listReserveColors(),
    ];
  }
  
  /**
   * Step 1 : player can choose a color in reserve cards
   * @throws BgaUserException
   */
  public function actCollectReserve(int $color): void
  {
    self::trace("actCollectReserve($color)");

    $player = Players::getCurrent();
    $pId = $player->getId();
    $this->addStep();

    // check input values
    $args = $this->argPlayerTurn();
    $reserveColors = $args['reserveColors'];
    if (!in_array($color, $reserveColors)) {
      throw new UnexpectedException(2,'Invalid color in reserve');
    }

    // Add your game logic to play a card here.
    Notifications::collectReserve($player,$color);
    $cards = Cards::moveReserveToPlayer($player,$color);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("next");
  }

  public function actPass(): void
  {
    // Retrieve the active player ID.
    $player_id = (int)$this->getActivePlayerId();

    // Notify all players about the choice to pass.
    $this->notifyAllPlayers("pass", clienttranslate('${player_name} passes'), [
        "player_id" => $player_id,
        "player_name" => $this->getActivePlayerName(),
    ]);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("pass");
  }
 
}
