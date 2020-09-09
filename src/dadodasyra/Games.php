<?php


namespace dadodasyra;

use pocketmine\item\Item;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;
use pocketmine\tile\Chest;
use Scoreboard\Scoreboard;

class Games
{
    /** @var array */
    public static $games;
    /** @var array */
    public static $timer;

    public static function getAllGames()
    {
        return ["1" => self::$games[1], "2" => self::$games[2], "3" => self::$games[3]];
    }

    public static function startGame(int $gameid)
    {
        $game = self::$games[$gameid];
        $players = [];
        $num = 0;
        $config = JumpLeague::$coords->get("game" . $gameid)["spawn"];
        $level = JumpLeague::getMain()->getServer()->getLevelByName(JumpLeague::$settings->get("game" . $gameid));

        self::generatechest($gameid);

        foreach ($game["players"] as $playername) $players[] = JumpLeague::getMain()->getServer()->getPlayerExact($playername);

        foreach ($players as $player) {
            $player->sendMessage(JumpLeague::getMessage("gamestart", []));
            $player->addTitle(JumpLeague::getMessage("titlestart", [], 1));
            $player->teleport(new Position($config["x"] + (100 * $num), $config["y"], $config["z"], $level));
            $player->namedtag->setInt("num", $num);
            $player->namedtag->setInt("modulex", $config["x"] + (100 * $num));
            $player->namedtag->setInt("moduley", $config["y"]);
            $player->namedtag->setInt("modulez", $config["z"]);
            $num++;
        }

        self::$timer[$gameid]["online"] = time();
        self::$games[$gameid]["online"] = true;
    }

    public static function combatstart(int $gameid, string $firstname)
    {
        $game = self::$games[$gameid];
        $players = [];
        $config = JumpLeague::$coords->get("game" . $gameid)["combat"];
        Games::$games[$gameid]["incombat"] = true;
        self::$timer[$gameid]["incombat"] = time();

        foreach ($game["players"] as $playername) $players[] = JumpLeague::getMain()->getServer()->getPlayerExact($playername);
        foreach ($players as $player) {
            $nbt = $player->namedtag;

            $coords = $config[$nbt->getInt("num")];
            $player->teleport(new Vector3($coords["x"], $coords["y"], $coords["z"]));
            $nbt->setInt("modulex", $coords["x"]);
            $nbt->setInt("moduley", $coords["y"]);
            $nbt->setInt("modulez", $coords["z"]);

            $player->addTitle(JumpLeague::getMessage("combatstarttitle", [], 1));

            if($player->getName() !== $firstname) {
                $player->sendMessage(JumpLeague::getMessage("combatstart", ["{first}" => $firstname]));
            }
        }
    }

    public static function endGame(int $gameid)
    {
        $game = self::$games[$gameid];
        $players = $game["players"];
        if (!count($players) >= 1) {
            JumpLeague::getMain()->getServer()->getLogger()->error("Endgame triggered but game not ended");
            return;
        }
        $sword = Item::get(Item::DIAMOND_SWORD);
        $sword->setCustomName("§bJumpLeague");
        $sword->setNamedTagEntry(new ByteTag("jumpleague", 1));

        $spectators = $game["spectators"];
        foreach ($players as $playername) {
            $winnername = $playername;
        }

        if (!isset($winnername)) {
            $winnername = "personne";
            $health = "aucun";
        } else {
            $winner = JumpLeague::getMain()->getServer()->getPlayerExact($winnername);
            $winner->sendMessage(JumpLeague::getMessage("gameendwin", ["{winner}" => $winner->getName(), "{pv}" => $winner->getHealth()]));
            $winner->addTitle(JumpLeague::getMessage("gameendwintitle", [], 1));
            $winner->teleport(JumpLeague::getMain()->getServer()->getDefaultLevel()->getSafeSpawn());
            $winner->getLevel()->addParticle(new ExplodeParticle($winner));
            $health = $winner->getHealth();

            $winner->setHealth($winner->getMaxHealth());
            $winner->setFood(20);
            $winner->getInventory()->clearAll();
            $winner->getInventory()->setItem(3, $sword);
            Scoreboard::getInstance()->remove($winner);
        }
        foreach ($spectators as $playername) {
            if ($playername !== $winnername) {
                $p = JumpLeague::getMain()->getServer()->getPlayerExact($playername);
                if($p instanceof Player){
                    $p->sendMessage(JumpLeague::getMessage("gameenddefeat", ["{winner}" => $winnername, "{pv}" => $health]));
                    $p->addTitle(JumpLeague::getMessage("gameendtitledefeat", [], 1));
                    $p->teleport(JumpLeague::getMain()->getServer()->getDefaultLevel()->getSafeSpawn());
                    $p->setFlying(false);
                    $p->setInvisible(false);
                    $p->setGamemode(0);

                    $p->getInventory()->clearAll();
                    $p->getInventory()->setItem(3, $sword);

                    Scoreboard::getInstance()->remove($p);
                }
            }
        }

        $game = ["online" => false,
            "full" => false,
            "ready" => false,
            "engagelaunch" => ["bool" => false, "id" => 0],
            "incombat" => false,
            "players" => [],
            "spectators" => []];

        Games::$games[$gameid] = $game;

        $timer = ["launch" => 0,
            "online" => 0,
            "incombat" => 0];

        Games::$timer[$gameid] = $timer;
    }

    public static function joinPlayer(Player $p)
    {
        foreach (Games::getAllGames() as $key => $game) {
            if (!$game["online"] && !$game["full"]) {
                array_push(self::$games[$key]["players"], $p->getName());
                if (count($game["players"]) >= 7) {
                    self::$games[$key]["full"] = true;
                }
                if (count($game["players"]) >= 3) {
                    self::$games[$key]["ready"] = true;
                }

                $level = JumpLeague::getMain()->getServer()->getLevelByName(JumpLeague::$settings->get("lobby" . $key));
                $p->teleport($level->getSafeSpawn());
                $p->sendMessage(JumpLeague::getMessage("joingame", ["{number}" => $key]));
                return;
            }
        }
        $p->sendMessage(JumpLeague::getMessage("fullgame"));
    }

    public static function leavePlayer(Player $p)
    {
        $name = $p->getName();
        $gamenum = self::isInGame($p);
        if (!$gamenum) return;

        if ($p->isOnline()) {
            $p->sendMessage(JumpLeague::getMessage("leavegame", ["{number}" => $gamenum]));
        }

        $game = self::$games[$gamenum];
        self::$games[$gamenum]["players"] = array_diff(self::$games[$gamenum]["players"], [$name]);
        self::$games[$gamenum]["spectators"] = array_diff(self::$games[$gamenum]["spectators"], [$name]);
        if (!$game["online"]) {
            if (count($game["players"]) <= 7) {
                self::$games[$gamenum]["full"] = false;
            }
            if (count($game["players"]) <= 3) {
                self::$games[$gamenum]["ready"] = false;
            }
        }

        if(count(Games::$games[$gamenum]["players"]) <= 1 && $game["incombat"]){
            self::endGame($gamenum);
        }
        $p->setFlying(false);
        $p->setInvisible(false);
        $p->setGamemode(0);
        $p->teleport(JumpLeague::getMain()->getServer()->getDefaultLevel()->getSafeSpawn());

        $p->getInventory()->clearAll();
        $sword = Item::get(Item::DIAMOND_SWORD);
        $sword->setCustomName("§bJumpLeague");
        $sword->setNamedTagEntry(new ByteTag("jumpleague", 1));
        $p->getInventory()->setItem(3, $sword);
        Scoreboard::getInstance()->remove($p);
    }

    public static function isInGame(Player $p)
    {
        $name = $p->getName();
        foreach (Games::getAllGames() as $key => $game) {
            if (in_array($name, $game["players"])) {
                return $key;
            }
        }

        return false;
    }

    public static function setSpecPlayer(Player $p)
    {
        //array_push(Games::$games[$gameid]["spectators"], $p->getName());

        $p->sendMessage(JumpLeague::getMessage("playerdeadspectator"));
        $p->addTitle(JumpLeague::getMessage("playerdeadspectatortitle", [], 1));

        $p->getInventory()->clearAll();
        $bed = Item::get(Item::BED);
        $bed->setCustomName("§cQuitter");
        $bed->setNamedTagEntry(new ByteTag("quitterjumpleague", 1));
        $p->getInventory()->setItem(1, $bed);
        $p->setFlying(true);
        $p->setGamemode(3);
    }

    public static function generatechest(int $gameid)
    {
        $listchests = JumpLeague::$coords->get("game" . $gameid)["chests"];
        $loottablescoords = Jumpleague::$coords->get("loottables");
        $loottableschest = [];
        if (!empty($loottablescoords && !empty($listchests))) {
            foreach ($loottablescoords as $loottablescoord) {
                $tile = JumpLeague::getMain()->getServer()->getLevelByName($loottablescoord["level"])->getTileAt($loottablescoord["x"], $loottablescoord["y"], $loottablescoord["z"]);
                if ($tile instanceof Chest) {
                    $loottableschest[] = $tile;
                }
            }

            foreach ($listchests as $chest) {
                $chest = JumpLeague::getMain()->getServer()->getLevelByName($chest["level"])->getTileAt($chest["x"], $chest["y"], $chest["z"]);
                if (!$chest instanceof Chest) break;
                $chest->getRealInventory()->clearAll();

                $selected = $loottableschest[mt_rand(0, count($loottableschest) - 1)];
                $chest->getRealInventory()->setContents($selected->getRealInventory()->getContents());
            }
        }
    }
}