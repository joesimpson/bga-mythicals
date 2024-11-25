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
 * states.inc.php
 *
 * Mythicals game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


/*
    "Visual" States Diagram :

                SETUP
                |
                |
                v
 /<----------- nextTurn     <-------------------\
 |              |                               ^
 |              v                               |
 |             cardCollect --\                  |
 |              |            |                  |
 |              v            |                  |
 |       tileChoice -->\     |                  |
 |              |      |     |                  |
 |              v      |     |                  |
 |       tileModif -->\|     |                  |
 |                    ||     |                  |
 |                    vv     v                  |
 |                    confirm --> endTurn ----->/
 v  
 \-> scoring
        | 
        v
        preEndOfGame
        | 
        v
        END
*/


$machinestates = [

    // The initial state. Please do not modify.
    ST_GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => ST_NEXT_TURN]
    ),

    ST_NEXT_TURN => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => [
            "end" => ST_END_SCORING,
            "nextPlayer" => ST_PLAYER_TURN_COLLECT,
        ]
    ],

    ST_PLAYER_TURN_COLLECT => [
        "name" => "cardCollect",
        "description" => clienttranslate('${actplayer} must collect cards'),
        "descriptionmyturn" => clienttranslate('${you} must collect cards'),
        "type" => "activeplayer",
        "args" => "argCardCollect",
        "possibleactions" => [
            "actDraw", 
            "actCollectDraw",
            "actCollectReserve", 
            'actUndoToStep', 'actRestart',
        ],
        "transitions" => [
            "draw" => ST_PLAYER_TURN_COLLECT,
            "next" => ST_PLAYER_TURN_TILE_CHOICE,
            "end" => ST_CONFIRM_TURN,
            "zombiePass"=> ST_CONFIRM_TURN,
        ],
    ],
    
    ST_PLAYER_TURN_TILE_CHOICE => [
        "name" => "tileChoice",
        "description" => clienttranslate('${actplayer} may take a mastery tile'),
        "descriptionmyturn" => clienttranslate('${you} may take a mastery tile'),
        "type" => "activeplayer",
        "args" => "argTileChoice",
        "action" => "stTileChoice",
        "possibleactions" => [
            "actTileChoice", 
            "actPass",
            'actUndoToStep', 'actRestart',
        ],
        "transitions" => [
            "next" => ST_PLAYER_TURN_TILE_MODIFICATION,
            "pass" => ST_CONFIRM_TURN,
            "zombiePass"=> ST_CONFIRM_TURN,
        ],
    ],
    
    ST_PLAYER_TURN_TILE_MODIFICATION => [
        "name" => "tileModif",
        "description" => clienttranslate('${actplayer} may reinforce or lock another mastery tile'),
        "descriptionmyturn" => clienttranslate('${you} may reinforce or lock another mastery tile'),
        "type" => "activeplayer",
        "args" => "argTileModif",
        "possibleactions" => [
            "actTileReinforce", 
            "actTileLock", 
            "actPass",
            'actUndoToStep', 'actRestart',
        ],
        "transitions" => [
            "next" => ST_CONFIRM_TURN,
            "pass" => ST_CONFIRM_TURN,
            "zombiePass"=> ST_CONFIRM_TURN,
        ],
    ],

    ST_CONFIRM_TURN => [
        'name' => 'confirmTurn',
        'description' => clienttranslate('${actplayer} must confirm or restart'),
        'descriptionmyturn' => clienttranslate('${you} must confirm or restart'),
        'type' => 'activeplayer',
        'args' => 'argsConfirmTurn',
        'action' => 'stConfirmTurn',
        'possibleactions' => [
            'actConfirmTurn', 
            'actUndoToStep', 'actRestart',
        ],
        'transitions' => [
          'confirm' => ST_END_TURN,
          'zombiePass'=> ST_END_TURN,
        ],
    ],

    ST_END_TURN => array(
        "name" => "endTurn",
        "description" => clienttranslate('End turn'),
        "type" => "game",
        "action" => "stEndTurn",
        "transitions" => [ 
            "next" => ST_NEXT_TURN,
        ],
    ),

    ST_END_SCORING => array(
        "name" => "scoring",
        "description" => clienttranslate('Scoring'),
        "type" => "game",
        "action" => "stScoring",
        "transitions" => [ 
            "next" => ST_PRE_END_OF_GAME,
        ],
    ),
    
    ST_PRE_END_OF_GAME => array(
        "name" => "preEndOfGame",
        "description" => '',
        "type" => "game",
        "action" => "stPreEndOfGame",
        "transitions" => [ 
            "next" => ST_END_GAME,
            //"next" => 96,
        ],
    ),
   
    //END GAME TESTING STATE
    /*
    96 => [
        "name" => "playerGameEnd",
        "description" => ('${actplayer} Game Over'),
        "descriptionmyturn" => ('${you} Game Over'),
        'type' => 'activeplayer',
        "args" => "argPlayerTurn",
        "args" => "argCardCollect",
        "possibleactions" => ["endGame"],
        "transitions" => [
            "next" => ST_END_GAME,
            "loopback" => 96 
        ] 
    ],
    */

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    ST_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];



