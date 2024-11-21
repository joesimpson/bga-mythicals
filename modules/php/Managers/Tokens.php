<?php

namespace Bga\Games\Mythicals\Managers;

use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Models\MasteryTile;
use Bga\Games\Mythicals\Models\Token;

/* Class to manage all the tokens */

class Tokens extends \Bga\Games\Mythicals\Helpers\Pieces
{
  protected static $table = 'tokens';
  protected static $prefix = 'token_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $data = self::getTokensTypes()[$type];
    return new Token($row, $data);
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all tokens visible by this player
   */
  public static function getUiData($currentPlayerId)
  {

    return 
      self::getAll()
      ->map(function ($token) {
        return $token->getUiData();
      })
      ->toArray();
  } 
 
  /**
   * Return all BOARD tokens 
   * @return Collection
   */
  public static function getBoardTokens()
  {
    return self::getFilteredQuery(null,TOKEN_LOCATION_BOARD)->get();
  }

  /**
   * @param int $pId
   * @param string $location (optional)
   * @return int number of ALL tokens owned by that player and in that $location,
   *   or ALL tokens owned by that player if location not given
   */
  public static function countPlayerTokens($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  
  /**
   * Return all HAND tokens of this player
   * @param int $pId
   * @return Collection
   */
  public static function getPlayerHand($pId)
  {
    return self::getFilteredQuery($pId, TOKEN_LOCATION_HAND)->get();
  }
  
  /**
   * @param MasteryTile $tile
   * @return Token
   */
  public static function addBonusMarkerOnTile(MasteryTile $tile)
  {
    /* We dont create it ! we get it from BOARD
    $token = [
      'type' => TOKEN_TYPE_BONUS_MARKER,
      'location' => TOKEN_LOCATION_TILE.$tile->getId(),
      'player_id' => null,
    ];
    $elt = self::singleCreate($token);
    */
    $token = self::getTopOf(TOKEN_LOCATION_BOARD);
    if(!isset($token)){
      throw new UnexpectedException(404,"No more bonus markers !");
    }
    $token->setLocation(TOKEN_LOCATION_TILE.$tile->getId());
    Notifications::newBonusMarkerOnTile($tile,$token);
    return $token;
  }

  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the tokens */
  public static function setupNewGame($players, $options)
  {
    $tokens = [];

    foreach (self::getTokensTypes() as $type => $token) {
      $tokens[] = [
        'location' => TOKEN_LOCATION_BOARD,
        'type' => $type,
        'nbr' => $token['nbr'],
      ];
    } 

    self::create($tokens);
  }
  
  /**
   * @return array of all the different types of Tokens
   */
  public static function getTokensTypes()
  {
    $f = function ($t) {
      return [
        'nbr' => $t[0],
      ];
    };
    return [
      TOKEN_TYPE_BONUS_MARKER => $f([ NB_BONUS_TOKEN_COPIES, ]), 
    
    ];
  }
}
