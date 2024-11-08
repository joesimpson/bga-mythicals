<?php
namespace Bga\Games\Mythicals\Exceptions;
use Bga\Games\Mythicals\Game;

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
