<?php

namespace Bga\Games\Mythicals\Models;

use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Exceptions\UserException;
use Bga\Games\Mythicals\Helpers\Collection;
use Bga\Games\Mythicals\Managers\Tokens;

class MasteryTile extends Tile
{
  
  protected $staticAttributes = [
    ['color', 'int'],
    ['score', 'int'],
    //The way to score this tile :
    ['scoring', 'int'],
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
    $data['pos'] = $this->getBoardPosition();
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

    $currentTokensNb = $this->getTokens()->count();
    if($currentTokensNb + $nbTokens > NB_MAX_TOKENS_ON_TILE){
      throw new UnexpectedException(405,"You cannot add $nbTokens bonus markers on this tile !");
    }

    for($k=0; $k<$nbTokens; $k++){
      $token = Tokens::addBonusMarkerOnTile($this);
      $newTokens->append($token);
    }

    return $newTokens;
  }
}
