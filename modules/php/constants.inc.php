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
 