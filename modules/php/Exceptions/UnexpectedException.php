<?php
namespace Bga\Games\MythicalsTheBoardGame\Exceptions;

use Bga\GameFramework\VisibleSystemException;

class UnexpectedException extends VisibleSystemException
{
    protected $code;

    public function __construct($code,$str)
    {
        parent::__construct($str);
        //$this->code = $code;
    }
}
?>
