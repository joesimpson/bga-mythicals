<?php
namespace Bga\Games\MythicalsTheBoardGame\Exceptions;

class UserException extends \Bga\GameFramework\UserException
{
    protected $code;

    public function __construct($code,$str)
    {
        //$this->code = $code;
        parent::__construct(($str));
    }
}
?>
