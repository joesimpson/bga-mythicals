<?php

namespace Bga\Games\Mythicals\States;

use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Stats;
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
      // Get some values from the current game situation from the database.

      return [
          "playableCardsIds" => [1, 2],
      ];
  }

  /**
   * Player action, example content.
   *
   * In this scenario, each time a player plays a card, this method will be called. This method is called directly
   * by the action trigger on the front side with `bgaPerformAction`.
   *
   * @throws BgaUserException
   */
  public function actPlayCard(int $card_id): void
  {
    // Retrieve the active player ID.
    $player_id = (int)$this->getActivePlayerId();

    // check input values
    $args = $this->argPlayerTurn();
    $playableCardsIds = $args['playableCardsIds'];
    if (!in_array($card_id, $playableCardsIds)) {
        throw new \BgaUserException('Invalid card choice');
    }

    // Add your game logic to play a card here.
    $card_name = Game::$CARD_TYPES[$card_id]['card_name'];

    // Notify all players about the card played.
    $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
        "player_id" => $player_id,
        "player_name" => $this->getActivePlayerName(),
        "card_name" => $card_name,
        "card_id" => $card_id,
        "i18n" => ['card_name'],
    ]);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("playCard");
  }

  public function actPass(): void
  {
    // Retrieve the active player ID.
    $player_id = (int)$this->getActivePlayerId();

    // Notify all players about the choice to pass.
    $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} passes'), [
        "player_id" => $player_id,
        "player_name" => $this->getActivePlayerName(),
    ]);

    // at the end of the action, move to the next state
    $this->gamestate->nextState("pass");
  }
 
}
