<?php

namespace dadodasyra\tasks;

use dadodasyra\Games;
use dadodasyra\JumpLeague;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class EngageLaunch extends Task
{
    /** @var int */
    public $gameid;
    /** @var int */
    public $lasttime;

    public function __construct(int $gameid)
    {
        $this->gameid = $gameid;
        $this->lasttime = 0;
    }

    public function onRun(int $currentTick)
    {
        $time = Games::$timer[$this->gameid]["launch"] - time();
        $players = [];

        foreach(Games::$games[$this->gameid]["players"] as $playername) $players[] = JumpLeague::getMain()->getServer()->getPlayerExact($playername);

        if (count(Games::$games[$this->gameid]["players"]) < 4){
            foreach($players as $player) {
                if ($player instanceof Player) {
                    $player->sendMessage(JumpLeague::getMessage("timercancellaunch", ["{seconds}" => $time]));
                }
            }
            JumpLeague::getMain()->getScheduler()->cancelTask($this->getTaskId());
            Games::$games[$this->gameid]["engagelaunch"]["bool"] = false;
            Games::$games[$this->gameid]["ready"] = false;
        }

        if($time > 15 && Games::$games[$this->gameid]["full"]){
            Games::$timer[$this->gameid]["launch"] = time() + 5;
            $time = 5;
        }

        if(in_array($time, JumpLeague::$settings->get("secondslaunch")) && $this->lasttime !== $time){
            $this->lasttime = $time;
            foreach($players as $player){
                if($player instanceof Player){
                    $player->sendMessage(JumpLeague::getMessage("timebeforelaunch", ["{seconds}" => $time]));
                }
            }
        }

        if($time <= 0){
            Games::startGame($this->gameid);
            JumpLeague::getMain()->getScheduler()->cancelTask($this->getTaskId());
        }
    }
}