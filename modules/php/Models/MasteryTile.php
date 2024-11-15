<?php

namespace Bga\Games\Mythicals\Models;

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
  
}
