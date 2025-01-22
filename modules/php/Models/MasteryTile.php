<?php

namespace Bga\Games\MythicalsTheBoardGame\Models;

use Bga\Games\MythicalsTheBoardGame\Core\Notifications;
use Bga\Games\MythicalsTheBoardGame\Exceptions\UnexpectedException;
use Bga\Games\MythicalsTheBoardGame\Exceptions\UserException;
use Bga\Games\MythicalsTheBoardGame\Helpers\Collection;
use Bga\Games\MythicalsTheBoardGame\Managers\Tokens;

class MasteryTile extends Tile
{
  
  protected $staticAttributes = [
    ['color', 'int'],
    ['score', 'int'],
    //The way to score this tile :
    //['scoring', 'int'],
  ];

  public function __construct($row, $datas)
  {
    parent::__construct($row, $datas);
  } 
  
  public function getUiData()
  {
    $data = parent::getUiData();
    $data['color'] = $this->getColor();
    $data['score'] = $this->getScore();
    //$data['scoring'] = $this->getBoardPosition();
    $data['pos'] = $this->getBoardPosition();
    //State is now used to stack tiles in hand
    $data['face'] = $this->getFace();// $this->getState();
    return $data;
  }

  /**
   * @return int position on board == scoring type during this game
   */
  public function getBoardPosition(){
    $location = $this->getLocation();
    if(str_starts_with($location, TILE_LOCATION_BOARD) ) 
      return intval(substr($location,strlen(TILE_LOCATION_BOARD)));
    return 0;
  }
  
  public function getNbEmptyTokenSpots() :int
  {
    $currentTokensNb = $this->getTokens()->count();
    if($currentTokensNb >= NB_MAX_TOKENS_ON_TILE){
      return 0;
    }
    return NB_MAX_TOKENS_ON_TILE - $currentTokensNb;
  }
  
  /**
   * @return Collection of Token
   */
  public function getTokens()
  {
    return Tokens::getInLocation(TOKEN_LOCATION_TILE.$this->getId());
  }
  
  /**
   * @param $nbTokens : number of tokens to add to current tokens
   * @return Collection added tokens
   */
  public function addBonus(int $nbTokens)
  {
    $newTokens = new Collection();

    if($nbTokens > $this->getNbEmptyTokenSpots()){
      throw new UnexpectedException(405,"You cannot add $nbTokens bonus markers on this tile !");
    }

    for($k=0; $k<$nbTokens; $k++){
      $token = Tokens::addBonusMarkerOnTile($this);
      $newTokens->append($token);
    }
    Notifications::newBonusMarkersOnTile($this,$newTokens);

    return $newTokens;
  }

  
  /**
   * @return int number of cards needed to be able to take that card
   */
  public function getNbCardsToDiscard() : int {
    $tileScoringType = $this->getBoardPosition();
    switch($tileScoringType){
      //------------------------------
      case TILE_SCORING_SUITE_2: return 2;
      case TILE_SCORING_SUITE_3: return 3;
      case TILE_SCORING_SUITE_4: return 4;
      case TILE_SCORING_SUITE_5: return 5;
      case TILE_SCORING_SUITE_6: return 6;
      //------------------------------
      case TILE_SCORING_SAME_2: return 2;
      case TILE_SCORING_SAME_3: return 3;
      case TILE_SCORING_SAME_4: return 4;
    }
    return 0;
  }
}
