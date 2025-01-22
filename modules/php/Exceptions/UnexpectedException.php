<?php
namespace Bga\Games\MythicalsTheBoardGame\Exceptions;
use Bga\Games\MythicalsTheBoardGame\Game;

class UnexpectedException extends \BgaVisibleSystemException
{
    protected $code;

    public function __construct($code,$str)
    {
        parent::__construct($str);
        $this->code = $code;
    }
}
?>
