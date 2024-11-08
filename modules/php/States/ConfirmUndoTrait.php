<?php

namespace Bga\Games\Mythicals\States;

use Bga\Games\Mythicals\Core\Globals;
use Bga\Games\Mythicals\Core\Notifications;
use Bga\Games\Mythicals\Exceptions\UnexpectedException;
use Bga\Games\Mythicals\Helpers\Log;
use Bga\Games\Mythicals\Managers\Players;

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
        $stepId = Log::step($this->gamestate->state_id());
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
        if (Globals::getChoices() == 0) {
            $this->actConfirmTurn(true);
        }
    }

    public function actConfirmTurn($auto = false)
    {
        if (!$auto) {
            self::checkAction('actConfirmTurn');
        }

        $player = Players::getCurrent();
        $pId = $player->getId(); 
        
        $this->gamestate->nextState('confirm');
    }


    public function actRestart()
    {
        self::checkAction('actRestart');
        $player = Players::getCurrent();
        $pId = $player->id;
        if (Globals::getChoices($pId) < 1) {
            throw new UnexpectedException(404,'No choice to undo. You may need to reload the page.');
        }
        Log::undoTurn();
        Notifications::restartTurn($player);
    }

    public function actUndoToStep($stepId)
    {
        self::checkAction('actRestart');
        $player = Players::getCurrent();
        $steps = Log::getUndoableSteps($player->id);
        if(!in_array($stepId,$steps)){
            throw new UnexpectedException(404,'This step is not undoable anymore. You may need to reload the page.');
        }
        Log::undoToStep($stepId);
        Notifications::undoStep($player, $stepId);
    }
}
