<?php

namespace Bga\Games\MythicalsTheBoardGame\Models;

/*
 * Tile: all utility functions concerning a Mastery Tile
 */

class Tile extends \Bga\Games\MythicalsTheBoardGame\Helpers\DB_Model
{
  protected $table = 'tiles';
  protected $primary = 'tile_id';
  protected $attributes = [
    'id' => ['tile_id', 'int'],
    'state' => ['tile_state', 'int'],
    'location' => 'tile_location',
    'pId' => ['player_id', 'int'],
    'type' => ['type', 'int'],
    'face' => ['face', 'int'],
  ];
   
  public function __construct($row, $datas)
  {
    parent::__construct($row);
    foreach ($datas as $attribute => $value) {
      $this->$attribute = $value;
    }
  }

  public function getUiData()
  {
    $data = parent::getUiData();
    return $data;
  }

}
