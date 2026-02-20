<?php

namespace Bga\Games\MythicalsTheBoardGame\States;

use Bga\GameFramework\Actions\Types\IntParam;
use Bga\Games\MythicalsTheBoardGame\Core\Globals;
use Bga\Games\MythicalsTheBoardGame\Core\Notifications;
use Bga\Games\MythicalsTheBoardGame\Exceptions\UnexpectedException;
use Bga\Games\MythicalsTheBoardGame\Game;
use Bga\Games\MythicalsTheBoardGame\Helpers\Log;
use Bga\Games\MythicalsTheBoardGame\Helpers\Utils;
use Bga\Games\MythicalsTheBoardGame\Managers\Players;

trait ConfirmUndoTrait
{
    /**
     * Add a NOT undoable step in Log module
     * @param int $state
     */
    public function addCheckpoint($state)
    {
        Globals::setChoices(0);
        Log::checkpoint($state);
    }

    /**
     * Add an undoable step in Log module
     */
    public function addStep()
    {
        $stepId = Log::step($this->gamestate->getCurrentMainStateId());
        Globals::incChoices();
    }

    public function argsConfirmTurn()
    {
        $activePlayer = Players::getActive();
        $data = [];
        $this->addArgsForUndo($data);
        return $data;
    }
    function addArgsForUndo(&$args)
    {
        $args['previousSteps'] = Log::getUndoableSteps();
        $args['previousChoices'] = Globals::getChoices();
    }

    public function stConfirmTurn()
    {
        $player = Players::getActive();
        if (Globals::getChoices() == 0 
            || $player->getPref(PREF_CONFIRM) == PREF_CONFIRM_DISABLED
        ) {//AUTO CONFIRM
            $version = Utils::gameVersion();
            $this->actConfirmTurn($version,true);
        }
    }

    public function actConfirmTurn(#[IntParam(name: 'v')] int $version, $auto = false)
    {
        Game::get()->checkVersion($version);
        if (!$auto) {
            //self::checkAction('actConfirmTurn');
        }

        $player = Players::getCurrent();
        $pId = $player->getId(); 
        
        $this->gamestate->nextState('confirm');
    }


    public function actRestart(#[IntParam(name: 'v')] int $version)
    {
        Game::get()->checkVersion($version);
        $player = Players::getCurrent();
        $pId = $player->getId();
        if (Globals::getChoices($pId) < 1) {
            throw new UnexpectedException(404,'No choice to undo. You may need to reload the page.');
        }
        Log::undoTurn();
        Notifications::restartTurn($player);
    }

    public function actUndoToStep(int $stepId, #[IntParam(name: 'v')] int $version): void
    {
        Game::get()->checkVersion($version);
        $player = Players::getCurrent();
        $pId = $player->getId();
        $steps = Log::getUndoableSteps($pId);
        if(!in_array($stepId,$steps)){
            throw new UnexpectedException(404,'This step is not undoable anymore. You may need to reload the page.');
        }
        Log::undoToStep($stepId);
        Notifications::undoStep($player, $stepId);
    }
}
