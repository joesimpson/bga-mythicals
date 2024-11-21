<?php
namespace Bga\Games\Mythicals;

use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Core\Stats;
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
    Notifications::refreshUI($this->getAllDatas());
    Stats::setupNewGame($playersDatas);
    Cards::setupNewGame($playersDatas,[]);
    Tiles::setupNewGame($players,[]);
    Tokens::setupNewGame($players,[]);
    Notifications::refreshUI($this->getAllDatas());
    
    $this->addCheckpoint(ST_PLAYER_TURN_COLLECT);
    $this->gamestate->jumpToState(ST_PLAYER_TURN_COLLECT);
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
  
  function debug_TMP(){
    $player = Players::getCurrent();
    $cards = Cards::countAll();
    Notifications::message(json_encode($cards));
  }
  */
  
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
}
