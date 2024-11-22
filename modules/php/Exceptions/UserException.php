<?php
namespace Bga\Games\Mythicals\Exceptions;
use Bga\Games\Mythicals\Game;

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
