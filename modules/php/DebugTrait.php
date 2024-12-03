<?php
namespace Bga\Games\Mythicals;

use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Helpers\QueryBuilder;
use Bga\Games\Mythicals\Helpers\Utils;
use Bga\Games\Mythicals\Managers\Cards;
use Bga\Games\Mythicals\Managers\Players;
use Bga\Games\Mythicals\Managers\Tiles;
use Bga\Games\Mythicals\Managers\Tokens;
use Bga\Games\Mythicals\Models\Token;

/**
 * Debugging functions to be called in chat window in BGA Studio
 */
trait DebugTrait
{
   /**
   * STUDIO : Get the database matching a bug report (when not empty)
   */
  public function loadBugReportSQL(int $reportId, array $studioPlayersIds): void {
    $this->trace("loadBugReportSQL($reportId, ".json_encode($studioPlayersIds));
    $players = $this->getObjectListFromDb('SELECT player_id FROM player', true);
  
    $sql = [];
    //This table is modified with boilerplate
    $sql[] = "ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;";

    // Change for your game
    // We are setting the current state to match the start of a player's turn if it's already game over
    $state = ST_PLAYER_TURN_COLLECT;
    $sql[] = "UPDATE global SET global_value=$state WHERE global_id=1 AND global_value=99";
    foreach ($players as $index => $pId) {
      $studioPlayer = $studioPlayersIds[$index];
  
      // All games can keep this SQL
      $sql[] = "UPDATE player SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE global SET global_value=$studioPlayer WHERE global_value=$pId";
      $sql[] = "UPDATE stats SET stats_player_id=$studioPlayer WHERE stats_player_id=$pId";
      $sql[] = "UPDATE bga_globals SET `value` = REPLACE(`value`,'$pId','$studioPlayer')";
  
      // Add game-specific SQL update the tables for your game
      $sql[] = "UPDATE cards SET player_id=$studioPlayer WHERE player_id = $pId";
    }
  
    foreach ($sql as $q) {
      $this->DbQuery($q);
    }
  
    $this->reloadPlayersBasicInfos();
  }

  /**
   * Function to call to regenerate JSON from PHP 
   */
  function debug_JSON(){
    include dirname(__FILE__) . '/gameoptions.inc.php';

    $customOptions = $game_options;//READ from module file
    $json = json_encode($customOptions, JSON_PRETTY_PRINT);
    //Formatting options as json -> copy the DOM of this log : \n
    Notifications::message("$json",['json' => $json]);
    
    $customOptions = $game_preferences;
    $json = json_encode($customOptions, JSON_PRETTY_PRINT);
    //Formatting prefs as json -> copy the DOM of this log : \n
    Notifications::message("$json",['json' => $json]);
  }
  ////////////////////////////////////////////////////
  
  function debug_UI(){
    self::reloadPlayersBasicInfos();
    Notifications::refreshUI($this->getAllDatas());
  }
  ////////////////////////////////////////////////////

  function debug_Setup(){
    $this->debug_ClearLogs();
    $players = self::loadPlayersBasicInfos();
    $playersDatas = Players::getAll();
    Stats::DB()->delete()->run();
    Cards::DB()->delete()->run();
    Tiles::DB()->delete()->run();
    Tokens::DB()->delete()->run();
    Globals::DB()->delete()->run();
    Notifications::refreshUI($this->getAllDatas());

    //Globals::setupNewGame($players,[]);
    //Stats::setupNewGame($playersDatas);
    //Cards::setupNewGame($playersDatas,[]);
    //Tiles::setupNewGame($players,[]);
    //Tokens::setupNewGame($players,[]);
    
    Players::DB()->delete()->run();
    $this->setupNewGame($players,[]);

    $players = self::loadPlayersBasicInfos();
    Notifications::refreshUI($this->getAllDatas());
    
    $this->addCheckpoint(ST_NEXT_TURN);
    $this->gamestate->jumpToState(ST_NEXT_TURN);
  }

  //Clear logs
  function debug_ClearLogs(){
    $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
    $query->delete()->run();
  }

  ////////////////////////////////////////////////////
  
  function debug_GoToPlayerTurn(){
    $this->gamestate->jumpToState(ST_PLAYER_TURN_COLLECT);
  }
  /*
  function debug_Reserve(){
    //Move all cards from deck to reserve, but not DAY CARD !
    Cards::moveAllInLocation(CARD_LOCATION_DECK, CARD_LOCATION_RESERVE);
    $cards = Cards::getInLocation(CARD_LOCATION_RESERVE);
    foreach($cards as $card){
      if(CARD_COLOR_DAY == $card->getColor()){
        $card->setLocation(CARD_LOCATION_DECK);
      }
    }
    $this->debug_UI();
    $this->gamestate->jumpToState(ST_PLAYER_TURN_COLLECT);
  }
  
  function debug_Duplicates(){
    $player = Players::getCurrent();
    $cards = Cards::listDuplicatesInPlayerHand($player);
    Notifications::message(json_encode($cards));
  }
  
  */
  
  function debug_DayCard(){
    $player = Players::getCurrent();
    //
    $cards = Cards::getAll();
    foreach($cards as $card){
      if($card->getColor() == CARD_COLOR_DAY){
        Notifications::dayCard($player,$card);
      }
    }
  }
  
  function debug_Suites(){
    $player = Players::getCurrent();
    Notifications::message("--------------------------------------------------");
    //
    $playerCards = Cards::getPlayerHand($player->getId());
      
    foreach(CARD_COLORS as $color){
      $tileColor = $color;
      $cardsOfTileColor = $playerCards->filter(
          function($card) use ($tileColor) { 
            return $tileColor == $card->getColor();
        });
        
      foreach([2,3,4,5] as $nbExpectedCards){
        $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards);
        Notifications::message("Suites of $nbExpectedCards cards of color $tileColor : ".json_encode(($possibleCards)));
      }
      $nbExpectedCards = 6;
      $possibleCards = Cards::listExistingSuites($cardsOfTileColor, $nbExpectedCards -1,true);
      Notifications::message("Suites of $nbExpectedCards cards of color $tileColor WITH JOKER value : ".json_encode(($possibleCards)));
    }

    //
    Notifications::message("All Suites : ".json_encode($this->listPossibleTilesToTake($player)));
    Notifications::message("--------------------------------------------------");
  }

  
  function debug_Sets(){
    $player = Players::getCurrent();
    Notifications::message("--------------------------------------------------");
    //
    $playerCards = Cards::getPlayerHand($player->getId());
    
    foreach([2,3,4] as $nbExpectedCards){
      $possibleCards = Utils::listExistingSameValues($playerCards, $nbExpectedCards);
      Notifications::message("Sets of $nbExpectedCards cards : ".json_encode(($possibleCards)));

      /*
      $possibleCardsArrays = (Utils::listSameValueSets($playerCards, $nbExpectedCards));
      //REMOVE DUPLICATES 
      $possibleCardsArrays = Utils::array_of_uniquearrays($possibleCardsArrays);
      $possibleCardsIds = [];
      foreach($possibleCardsArrays as $possibleCardsArray){

        $possibleCards = new Collection($possibleCardsArray);
        $possibleCardsIds[] = $possibleCards->map(function($card){return $card->getId();})->toArray();
      }
      Notifications::message("Sets of $nbExpectedCards cards : ".json_encode(($possibleCardsIds)));
      */
    }
    //
    Notifications::message("--------------------------------------------------");
  }
  
  function debug_SpecificSet(){
    $cards_ids = [14,25,36];
    $cards_ids = [25,36];
    $player = Players::getCurrent();
    Notifications::message("--------------------------------------------------");
    //
    $playerCards = Cards::getPlayerHand($player->getId());
    $cards = $playerCards->filter(function($card) use ($cards_ids){return in_array($card->getId(), $cards_ids);});
    
    $isOk = Utils::isSameValueSet($cards);
    Notifications::message("Is set of cards  ".json_encode(($cards->getIds()))." ...: ".json_encode($isOk));
    //
    Notifications::message("--------------------------------------------------");
  }
  
  //Test placing tokens when no more available
  function debug_noMoreTokens(){
    $player = Players::getCurrent();
    Tokens::moveAllInLocation(TOKEN_LOCATION_BOARD,'tmp'.TOKEN_LOCATION_BOARD);
    //MOVE 1 AGAIN
    $token = Tokens::getTopOf('tmp'.TOKEN_LOCATION_BOARD);
    $token->setLocation(TOKEN_LOCATION_BOARD);
    $this->debug_UI();
  }
  
  //Test locking tiles when no more available
  function debug_noMoreTiles(){
    $player = Players::getCurrent();
    $tiles = Tiles::getBoardTiles();
    foreach($tiles as $tile){
      $tile->setLocation('tmp'.$tile->getLocation());
    }

    $this->debug_UI();
  }
  
  function debug_Scoring(){
    $players = Players::getAll();
    foreach($players as $player) $player->setScore(0);
    $this->debug_UI();
    $this->computeFinalScore($players);
  }
  function debug_GoToScoring(){
    $players = Players::getAll();
    foreach($players as $player) $player->setScore(0);
    $this->debug_UI();
    $this->gamestate->jumpToState(ST_END_SCORING);
  }

  
  
  function debug_Zombie(){
    $player = Players::getActive();
    $playerId = $player->getId();
    $state = Game::get()->gamestate->state();
    Game::get()->zombieTurn($state,$playerId);
  }

  function debug_RealTime(): void {
    $players = Players::getAll();
  
    $sql = [];
    //RESET TO TIMEMODE NORMAL with 120s
      $sql[] = "UPDATE `global` SET `global_value` = '180' WHERE `global`.`global_id` = 8; ";
      $sql[] = "UPDATE `global` SET `global_value` = '120' WHERE `global`.`global_id` = 9; ";
      $sql[] = "UPDATE `global` SET `global_value` = '1' WHERE `global`.`global_id` = 200; ";
      $sql[] = "UPDATE `global` SET `global_value` = '0' WHERE `global`.`global_id` = 201; ";
  
      foreach ($players as $pId => $player) {
        $sql[] = "UPDATE `player` SET `player_remaining_reflexion_time` = '150' WHERE `player`.`player_id` = $pId; ";
      }

    foreach ($sql as $q) {
      $this->DbQuery($q);
    }
  
    $this->reloadPlayersBasicInfos();
    //THEN REFRESH PAGE
  }
  
  function debug_TurnBasedNoLimit(): void {
    $players = Players::getAll();
  
    $sql = [];
      $sql[] = "UPDATE `global` SET `global_value` = '7776000' WHERE `global`.`global_id` = 8; ";
      $sql[] = "UPDATE `global` SET `global_value` = '2592000' WHERE `global`.`global_id` = 9; ";
      $sql[] = "UPDATE `global` SET `global_value` = '20' WHERE `global`.`global_id` = 200; ";
      $sql[] = "UPDATE `global` SET `global_value` = '1' WHERE `global`.`global_id` = 201; ";
  
      foreach ($players as $pId => $player) {
        $sql[] = "UPDATE `player` SET `player_remaining_reflexion_time` = '7776000' WHERE `player`.`player_id` = $pId; ";
      }

    foreach ($sql as $q) {
      $this->DbQuery($q);
    }
  
    $this->reloadPlayersBasicInfos();
    //THEN REFRESH PAGE
  }


}
