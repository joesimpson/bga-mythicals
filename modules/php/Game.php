<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Mythicals implementation : © joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\Mythicals;

use \Bga\GameFramework\Actions\CheckAction;
use Bga\Games\Mythicals\Core\Preferences;
use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;
use Bga\Games\Mythicals\Managers\Tokens;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");
require_once 'constants.inc.php';

class Game extends \Table
{    
    use DebugTrait;
    use States\ConfirmUndoTrait;
    use States\EndTurnTrait;
    use States\NextTurnTrait;
    use States\PlayerTurnTrait;
    use States\TileChoiceTrait;
    use States\TileModifTrait;
    use States\SetupTrait;
    use States\ScoringTrait;

    public static $instance = null;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();
        self::$instance = $this;

        $this->initGameStateLabels([
            'logging' => 10,
        ]);        
    }

    public static function get()
    {
      return self::$instance;
    }

    //-> See States package for game states arguments and actions

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $nbPlayers = Players::count();
        $initialDeckSize = Cards::countAll() - $nbPlayers * NB_CARDS_PER_PLAYER;
        $deckSize = Cards::countInLocation(CARD_LOCATION_DECK);
        $progress = ($initialDeckSize - $deckSize) / $initialDeckSize;

        return $progress * 100;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    public function getAllDatas()
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        $result["players"] = Players::getUiData($current_player_id);
        $result["cards"] = Cards::getUiData($current_player_id);
        $result["deckSize"] = Cards::countInLocation(CARD_LOCATION_DECK);
        $result["tiles"] = Tiles::getUiData($current_player_id);
        $result["tokens"] = Tokens::getUiData($current_player_id);
        $result["prefs"] = Preferences::getUiData($current_player_id);

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "mythicals";
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }

    #[CheckAction(false)]
    public function actChangePref(int $pref, int $value): void
    {
        //TODO JSA check if useless from boilerplate
        //Preferences::set($this->getCurrentPId(), $pref, $value);
    }
    
    /////////////////////////////////////////////////////////////
    // Exposing protected methods, please use at your own risk //
    /////////////////////////////////////////////////////////////

    // Exposing protected method getCurrentPlayerId
    public function getCurrentPId($bReturnNullIfNotLogged = false)
    {
        return $this->getCurrentPlayerId($bReturnNullIfNotLogged);
    }
    // Exposing protected method translation
    public function translate($text)
    {
        return $this->_($text);
    }
}
