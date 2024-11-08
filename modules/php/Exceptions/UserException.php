<?php
namespace Bga\Games\Mythicals\Exceptions;
use Bga\Games\Mythicals\Game;

class UserException extends \BgaUserException
{
    public function __construct($str)
    {
        parent::__construct(Game::get()->translate($str));
    }
}
?>
