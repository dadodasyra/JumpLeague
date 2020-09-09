<?php


namespace dadodasyra;


use dadodasyra\tasks\EngageLaunch;
use pocketmine\scheduler\Task;

class GameTask extends Task
{
    public function onRun(int $currentTick)
    {
        foreach (Games::getAllGames() as $key => $game) {
            if ($game["ready"] && !$game["engagelaunch"]["bool"]) {
                Games::$games[$key]["engagelaunch"]["bool"] = true;
                Games::$games[$key]["engagelaunch"]["id"] = JumpLeague::getMain()->getScheduler()->scheduleRepeatingTask(new EngageLaunch($key), 15)->getTaskId();
                Games::$timer[$key]["launch"] = time() + 5;
            }
        }
    }
}