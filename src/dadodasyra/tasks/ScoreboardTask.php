<?php


namespace dadodasyra\tasks;


use dadodasyra\Games;
use dadodasyra\JumpLeague;
use pocketmine\scheduler\Task;
use Scoreboard\Scoreboard;

class ScoreboardTask extends Task
{
    public function onRun(int $currentTick)
    {
        $api = Scoreboard::getInstance();
        foreach (Games::getAllGames() as $key => $game) {
            if(!$game["engagelaunch"]["bool"]){
                JumpLeague::getMessage("playersscoreboard", ["{current}" => count($game["players"])]);
                JumpLeague::getMessage("waitscoreboard", [], 1);
                $api->new($key,1, "Ligne 1");
                $api->new($key,3, "Ligne 3");
                return;
            }
/*
            if($game["engagelaunch"]["bool"]){
                $time = $this->calculTime(Games::$timer[$key]["launch"] - time());

                Scoreboard::setScoreboardEntry($key, JumpLeague::getMessage("playersscoreboard", ["{current}" => count($game["players"])], 1);
                Scoreboard::setScoreboardEntry($key, JumpLeague::getMessage("beforelaunchscoreboard", ["{sec}" => $time["s"]], 1);
                return;
            }

            if($game["incombat"]){
                $time = $this->calculTime(time() - Games::$timer[$key]["incombat"]);

                Scoreboard::setScoreboardEntry($key, JumpLeague::getMessage("playersscoreboard", ["{current}" => count($game["players"])], 1), 1);
                Scoreboard::setScoreboardEntry($key, JumpLeague::getMessage("duringtimescoreboard", ["{min}" => $time["m"], "{sec}" => $time["s"]], 1), 2);
                return;
            } else if($game["online"]){
                $time = $this->calculTime(time() - Games::$timer[$key]["online"]);

                Scoreboard::setScoreboardEntry($key, JumpLeague::getMessage("playersscoreboard", ["{current}" => count($game["players"])], 1), 1);
                Scoreboard::setScoreboardEntry($key, JumpLeague::getMessage("duringtimescoreboard", ["{min}" => $time["m"], "{sec}" => $time["s"]], 1), 2);
                return;
            }*/
        }
    }

    public function calculTime(int $base): array
    {
        $min = $base / 60;
        $sec = $base % 60;

        return ["m" => $min, "s" => $sec];
    }
}