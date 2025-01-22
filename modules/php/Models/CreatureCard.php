<?php

namespace Bga\Games\MythicalsTheBoardGame\Models;

class CreatureCard extends Card
{
  
  protected $staticAttributes = [
    ['color', 'int'],
    ['value', 'int'],
  ];

  public function __construct($row, $datas)
  {
    parent::__construct($row, $datas);
  } 
  
  public function getUiData()
  {
    $data = parent::getUiData();
    $data['color'] = $this->getColor();
    $data['value'] = $this->getValue();
    return $data;
  }
  
}
