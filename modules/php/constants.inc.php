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
const CARD_LOCATION_HAND = 'hand';

const NB_CARDS_PER_PLAYER = 2;

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
 