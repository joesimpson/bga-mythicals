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
    $data['scoring'] = $this->getScoring();
    return $data;
  }
  
}
