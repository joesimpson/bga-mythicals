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
 * gameoptions.inc.php
 *
 * Mythicals game options description
 * 
 * NB : 11/2023 new JSON format you can generate it from this file with PHP : 
 * call the debug function from chat :
 *    debugJSON()
 *
 */

 namespace Bga\Games\Mythicals;

//if placed at root folder
//require_once 'modules/php/constants.inc.php';
//Else near constants :
require_once 'constants.inc.php';

$game_options = [

];

$game_preferences = [
 
  PREF_UNDO_STYLE => [
    'name' => totranslate('Undo buttons style'),
    'needReload' => false,
    'values' => [
      PREF_UNDO_STYLE_TEXT => [ 'name' => totranslate('Text') ],
      PREF_UNDO_STYLE_ICON => [ 'name' => totranslate('Icon')],
    ],
    "default"=> PREF_UNDO_STYLE_ICON,
    'attribute' => 'myt_undo_style',
  ],

  PREF_CONFIRM => [
    'name' => totranslate('Ask for turn confirmation'),
    'needReload' => false,
    'values' => [
      PREF_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      PREF_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
    "default"=> PREF_CONFIRM_DISABLED,
  ],

  PREF_CARD_STACK_STYLE => [
    'name' => totranslate('Card stack order'),
    'needReload' => false,
    'values' => [
      PREF_CARD_STACK_ASC => [ 'name' => '1,2,3,4,5,*' ],
      PREF_CARD_STACK_DESC => [ 'name' => '*,5,4,3,2,1'],
      //We could add "*,1,2,3,4,5" and "5,4,3,2,1,*"
    ],
    "default"=> PREF_CARD_STACK_ASC,
    'attribute' => 'myt_card_stack_order',
  ],
 
];
