<?php
 
const BGA_GAMESTATE_GAMEVERSION = 300;

/*
 * Game Constants
 */
  
/////////////////////////////////////////////////////////
//          CARDS
/////////////////////////////////////////////////////////

const CARD_LOCATION_DISCARD = 'discard';
const CARD_LOCATION_DECK = 'deck';
const CARD_LOCATION_CURRENT_DRAW = 'draw';
const CARD_LOCATION_RESERVE = 'reserve';
const CARD_LOCATION_HAND = 'hand';

const NB_CARDS_PER_PLAYER = 2;
/** there are 2 copies of each creature */
const NB_CREATURE_COPIES = 2;

const CARD_COLOR_BLUE = 1;
const CARD_COLOR_GREEN = 2;
const CARD_COLOR_PURPLE = 3;
const CARD_COLOR_RED = 4;
//The day card will be unique
const CARD_COLOR_DAY = 9;

const CARD_COLORS = [
   CARD_COLOR_BLUE, 
   CARD_COLOR_GREEN, 
   CARD_COLOR_PURPLE, 
   CARD_COLOR_RED
];

const CARD_VALUE_1 = 1;
const CARD_VALUE_2 = 2;
const CARD_VALUE_3 = 3;
const CARD_VALUE_4 = 4;
const CARD_VALUE_5 = 5;
const CARD_VALUE_JOKER = 6;

/////////////////////////////////////////////////////////
//          TILES
/////////////////////////////////////////////////////////

const TILE_LOCATION_BOARD = 'board';
const TILE_LOCATION_HAND = 'hand';

const TILE_COLOR_BLUE = 1;
const TILE_COLOR_GREEN = 2;
const TILE_COLOR_PURPLE = 3;
const TILE_COLOR_RED = 4;
const TILE_COLOR_GRAY = 5;
const TILE_COLOR_BLACK = 6;

const TILE_COLORS = [
   TILE_COLOR_BLUE,
   TILE_COLOR_GREEN,
   TILE_COLOR_PURPLE,
   TILE_COLOR_RED,
   TILE_COLOR_GRAY,
   TILE_COLOR_BLACK,
];

/** there are 1 copies of each tile */
const NB_TILE_COPIES = 1;

const TILE_SCORE_2 = 2;
const TILE_SCORE_4 = 4;
const TILE_SCORE_7 = 7;
const TILE_SCORE_10 = 10;
const TILE_SCORE_13 = 13;

const TILE_SCORING_SUITE_2 = 1;
const TILE_SCORING_SUITE_3 = 2;
const TILE_SCORING_SUITE_4 = 3;
const TILE_SCORING_SUITE_5 = 4;
const TILE_SCORING_SAME_2 = 5;
const TILE_SCORING_SAME_3 = 6;
const TILE_SCORING_SAME_4 = 7;
const TILE_SCORING_SAME_6 = 8;


//A tile is either on OPEN side or LOCKED side
const TILE_STATE_OPEN = 1;
const TILE_STATE_LOCKED = 2;

/////////////////////////////////////////////////////////
//          MEEPLES
/////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////
//          Game options
/////////////////////////////////////////////////////////  
/*
const OPTION_EXPANSION_XXX = 110;
const OPTION_EXPANSION_OFF = 0;
const OPTION_EXPANSION_ON = 1;
*/
/////////////////////////////////////////////////////////
//          User preferences
/////////////////////////////////////////////////////////  
const PREF_UNDO_STYLE = 101;
const PREF_UNDO_STYLE_TEXT = 1;
const PREF_UNDO_STYLE_ICON = 2;

const ALL_PREFERENCES = [
   PREF_UNDO_STYLE,
];
/////////////////////////////////////////////////////////
//          GAME STATES
/////////////////////////////////////////////////////////  
const ST_GAME_SETUP = 1;
 
const ST_NEXT_TURN = 10;

const ST_PLAYER_TURN = 20; 

const ST_CONFIRM_CHOICES = 70;
const ST_CONFIRM_TURN = 71;
const ST_END_TURN = 80;

const ST_END_SCORING = 90;
const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;
 