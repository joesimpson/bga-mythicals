<?php
namespace Bga\Games\MythicalsTheBoardGame\Exceptions;
use Bga\Games\MythicalsTheBoardGame\Game;

class UserException extends \BgaUserException
{
    protected $code;

    public function __construct($code,$str)
    {
        $this->code = $code;
        parent::__construct(Game::get()->translate($str));
    }
}
?>
