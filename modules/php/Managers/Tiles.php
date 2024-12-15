<?php

namespace Bga\Games\Mythicals\Managers;

use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Models\MasteryTile;

/* Class to manage all the Tiles */

class Tiles extends \Bga\Games\Mythicals\Helpers\Pieces
{
  protected static $table = 'tiles';
  protected static $prefix = 'tile_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type', 'face'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $data = self::getMasteryTilesTypes()[$type];
    return new MasteryTile($row, $data);
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all tiles visible by this player
   */
  public static function getUiData($currentPlayerId)
  {
    //$privateTiles = self::getPlayerHand($currentPlayerId);
    $players = Players::getAll();

    $revealTiles = Globals::isScoringDone();

    $tiles = self::getBoardTiles();
    //GAME RULE: we can see only 1 tile on top of tiles stack of each player
    foreach($players as $pId => $p){
      if(isset($revealTiles) && $revealTiles){
        $hand = Tiles::getPlayerHand($pId);
        foreach($hand as $tile) $tiles->append($tile);
        continue;
      }
      $tile = self::getTopOfPlayerLoc($pId,TILE_LOCATION_HAND);
      if(isset($tile)) $tiles->append($tile);
    }
    
    return $tiles
      ->map(function ($tile) {
        return $tile->getUiData();
      })
      ->toArray();
  } 
 
  /**
   * Return all BOARD tiles 
   * @return Collection
   */
  public static function getBoardTiles()
  {
    return self::getFilteredQuery(null,TILE_LOCATION_BOARD.'%')->get();
  }

  /**
   * @param int $pId
   * @param string $location (optional)
   * @return int number of ALL tiles owned by that player and in that $location,
   *   or ALL tiles owned by that player if location not given
   */
  public static function countPlayerTiles($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  
  /**
   * Return all HAND tiles of this player
   * @param int $pId
   * @return Collection
   */
  public static function getPlayerHand($pId)
  {
    return self::getFilteredQuery($pId, TILE_LOCATION_HAND)->get();
  }
  

  /**
   * @param int $scoringType
   * @return string
   */
  public static function formatBoardLocation(int $scoringType){
    return TILE_LOCATION_BOARD."$scoringType";
  }

  public static function getColorName(int $color)
  { 
    switch($color){
      case TILE_COLOR_BLUE:
        return clienttranslate("blue");
      case TILE_COLOR_RED:
        return clienttranslate("red");
      case TILE_COLOR_PURPLE:
        return clienttranslate("purple");
      case TILE_COLOR_GREEN:
        return clienttranslate("green");
      case TILE_COLOR_BLACK:
        return clienttranslate("black");
      case TILE_COLOR_GRAY:
        return clienttranslate("gray");
      default: 
        return "";
    }
  }
   
  /** Creation of the tiles */
  public static function setupNewGame($players, $options)
  {
    $tiles = [];

    foreach (self::getMasteryTilesTypes() as $type => $tile) {
      $tiles[] = [
        'location' => self::formatBoardLocation($tile['defaultScoring']),
        'type' => $type,
        'nbr' => $tile['nbr'],
        'face' => TILE_FACE_OPEN,
      ];
    } 

    self::create($tiles);
  }
  
  /**
   * @return array of all the different types of Mastery tiles
   */
  public static function getMasteryTilesTypes()
  {
    $f = function ($t) {
      return [
        'nbr' => $t[0],
        'color' => $t[1],
        'score' => $t[2],
        'defaultScoring' => $t[3],
      ];
    };
    return [
      1 => $f([ NB_TILE_COPIES, TILE_COLOR_PURPLE, TILE_SCORE_2 , TILE_SCORING_SUITE_2, ]), 
      2 => $f([ NB_TILE_COPIES, TILE_COLOR_PURPLE, TILE_SCORE_4 , TILE_SCORING_SUITE_3, ]), 
      3 => $f([ NB_TILE_COPIES, TILE_COLOR_PURPLE, TILE_SCORE_7 , TILE_SCORING_SUITE_4, ]), 
      4 => $f([ NB_TILE_COPIES, TILE_COLOR_PURPLE, TILE_SCORE_10, TILE_SCORING_SUITE_5, ]), 

      5 => $f([ NB_TILE_COPIES, TILE_COLOR_GREEN, TILE_SCORE_2 , TILE_SCORING_SUITE_2, ]), 
      6 => $f([ NB_TILE_COPIES, TILE_COLOR_GREEN, TILE_SCORE_4 , TILE_SCORING_SUITE_3, ]), 
      7 => $f([ NB_TILE_COPIES, TILE_COLOR_GREEN, TILE_SCORE_7 , TILE_SCORING_SUITE_4, ]), 
      8 => $f([ NB_TILE_COPIES, TILE_COLOR_GREEN, TILE_SCORE_10, TILE_SCORING_SUITE_5, ]), 

      9 => $f([ NB_TILE_COPIES, TILE_COLOR_RED, TILE_SCORE_2 , TILE_SCORING_SUITE_2, ]), 
      10 => $f([ NB_TILE_COPIES, TILE_COLOR_RED,TILE_SCORE_4 , TILE_SCORING_SUITE_3, ]), 
      11 => $f([ NB_TILE_COPIES, TILE_COLOR_RED,TILE_SCORE_7 , TILE_SCORING_SUITE_4, ]), 
      12 => $f([ NB_TILE_COPIES, TILE_COLOR_RED,TILE_SCORE_10, TILE_SCORING_SUITE_5, ]), 

      13 => $f([ NB_TILE_COPIES, TILE_COLOR_BLUE, TILE_SCORE_2 , TILE_SCORING_SUITE_2, ]), 
      14 => $f([ NB_TILE_COPIES, TILE_COLOR_BLUE, TILE_SCORE_4 , TILE_SCORING_SUITE_3, ]), 
      15 => $f([ NB_TILE_COPIES, TILE_COLOR_BLUE, TILE_SCORE_7 , TILE_SCORING_SUITE_4, ]), 
      16 => $f([ NB_TILE_COPIES, TILE_COLOR_BLUE, TILE_SCORE_10, TILE_SCORING_SUITE_5, ]), 

      17 => $f([ NB_TILE_COPIES, TILE_COLOR_GRAY, TILE_SCORE_2 , TILE_SCORING_SAME_2, ]),
      18 => $f([ NB_TILE_COPIES, TILE_COLOR_GRAY, TILE_SCORE_4 , TILE_SCORING_SAME_3, ]),
      19 => $f([ NB_TILE_COPIES, TILE_COLOR_GRAY, TILE_SCORE_7 , TILE_SCORING_SAME_4, ]),
      
      20 => $f([ NB_TILE_COPIES, TILE_COLOR_BLACK, TILE_SCORE_13, TILE_SCORING_SUITE_6, ]), 
    
    ];
  }
}
