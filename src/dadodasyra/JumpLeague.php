<?php

namespace dadodasyra;

use dadodasyra\commands\Commands;
use dadodasyra\tasks\ScoreboardTask;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class JumpLeague extends PluginBase
{
    /** @var JumpLeague */
    protected static $instance;

    /** @var Config */
    public static $messages;
    /** @var Config */
    public static $settings;
    /** @var Config */
    public static $coords;

    public function onEnable()
    {
        self::$instance = $this;

        if (!file_exists($this->getDataFolder() . "messages.yml")) {
            $this->saveResource("messages.yml");
        }
        self::$messages = new Config($this->getDataFolder() . "messages.yml");
        if (!file_exists($this->getDataFolder() . "settings.yml")) {
            $this->saveResource("settings.yml");
        }
        self::$settings = new Config($this->getDataFolder() . "settings.yml");
        if (!file_exists($this->getDataFolder() . "coords.yml")) {
            $this->saveResource("coords.yml");
        }
        self::$coords = new Config($this->getDataFolder() . "coords.yml");

        $this->getServer()->getPluginManager()->registerEvents(new JumpListener(), $this);
        //$this->getServer()->getPluginManager()->registerEvents(new Scoreboard(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new GameTask(), 15);
        $this->getScheduler()->scheduleRepeatingTask(new ScoreboardTask(), 20);

        $this->preparegames();
        $this->getServer()->getCommandMap()->registerAll("jl", [new Commands()]);
    }

    public static function getMessage(string $key, array $replacements = [], int $type = 0) //Cette fonction vient d'un dev anglais, kielking
    {
        $str = self::$messages->get($key);
        $str = str_replace("&", "§", $str);
        foreach($replacements as $find => $replace){
            $str = str_replace($find, $replace, $str);
        }

        switch ($type){
            case 0:
                return self::$messages->get("prefix").$str;
            default:
                return $str;
        }
    }

    public static function getMain()
    {
        return self::$instance;
    }

    public function preparegames()
    {
        $sett = self::$settings;
        $levels = [ $sett->get("source"),
            $sett->get("lobby1"), $sett->get("lobby2"), $sett->get("lobby3"),
            $sett->get("game1"), $sett->get("game2"), $sett->get("game3")];

        foreach ($levels as $level){
            $this->getServer()->loadLevel($level);
        }

        for($i = 1; $i <= 3; $i++){
            $game = ["online" => false,
                "full" => false,
                "ready" => false,
                "engagelaunch" => ["bool" => false, "id" => 0],
                "incombat" => false,
                "players" => [],
                "spectators" => []];

            Games::$games[$i] = $game;

            $timer = ["launch" => 0,
                "ig" => 0];

            Games::$timer[$i] = $timer;
        }
    }
}