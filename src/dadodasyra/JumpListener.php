<?php


namespace dadodasyra;

use dadodasyra\commands\Commands;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;

class JumpListener implements Listener
{
    public function onJoin(PlayerJoinEvent $event)
    {
        $event->getPlayer()->getInventory()->clearAll();
        $sword = Item::get(Item::DIAMOND_SWORD);
        $sword->setCustomName("§bJumpLeague");
        $sword->setNamedTagEntry(new ByteTag("jumpleague", 1));
        $event->getPlayer()->getInventory()->setItem(3, $sword);
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        Games::leavePlayer($event->getPlayer());
    }

    public function onInteract(PlayerInteractEvent $event)
    {
        $p = $event->getPlayer();

        if ($event->getItem()->getId() === Item::DIAMOND_SWORD && $event->getItem()->getNamedTag()->hasTag("jumpleague")) {
            $p->getInventory()->clearAll();
            Games::joinPlayer($event->getPlayer());
        }

        if ($event->getItem()->getId() === Item::BED && $event->getItem()->getNamedTag()->hasTag("quitterjumpleague")) {
            Games::leavePlayer($p);
            $p->getInventory()->clearAll();
            $sword = Item::get(Item::DIAMOND_SWORD);
            $sword->setCustomName("§bJumpLeague");
            $sword->setNamedTagEntry(new ByteTag("jumpleague", 1));
            $p->getInventory()->setItem(3, $sword);
        }

        $b = $event->getBlock();
        if ($b->getId() === Block::CHEST) {
            $loottable = Commands::$loottable;
            $chests = Commands::$chests;
            if (isset($chests[$p->getName()]) && $chests[$p->getName()]) {
                $gameid = Commands::getGame($p);
                $conf = JumpLeague::$coords->get("game".$gameid);
                $conf["chests"][] = ["x" => $b->x, "y" => $b->y, "z" => $b->z, "level" => $b->getLevel()->getName()];

                JumpLeague::$coords->set("game".$gameid, $conf);
                JumpLeague::$coords->save();

                $p->sendMessage(Commands::PREF."§aLe coffre a bien été enregistrée");
            } else if (isset($loottable[$p->getName()]) && $loottable[$p->getName()]) {
                $conf = JumpLeague::$coords->get("loottables");
                $conf[] = ["x" => $b->x, "y" => $b->y, "z" => $b->z, "level" => $b->getLevel()->getName()];

                JumpLeague::$coords->set("loottables", $conf);
                JumpLeague::$coords->save();

                $p->sendMessage(Commands::PREF."§aLa loottable a bien été enregistrée");
            }
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        if (!$event->getTo()->distance($event->getFrom()) >= 0.1) return;
        $p = $event->getPlayer();
        $game = Games::isInGame($p);
        if($game === false) return;
        if(!Games::$games[$game]["online"]) return;
        $nbt = $p->namedtag;
        $arrive = JumpLeague::$coords->get("game".$game)["arrive"];

        if($p->getFloorX() === $arrive["x"] + ($nbt->getInt("num") * 100) && $p->getFloorY() === $arrive["y"] && $p->getFloorZ() === $arrive["z"]){
            $p->sendMessage(JumpLeague::getMessage("firstonjump"));
            Games::combatstart($game, $p->getName());
            return;
        }

        if($p->getLevel()->getBlock($p)->getId() === Block::STONE_PRESSURE_PLATE) {
            $cos = [$p->getFloorX(), $p->getFloorY(), $p->getFloorZ()];
            if($nbt->getInt("modulex") !== $cos[0] || $nbt->getInt("moduley") !== $cos[1] || $nbt->getInt("modulez") !== $cos[2]){
                $p->sendMessage(JumpLeague::getMessage("moduleannounce"));

                $nbt->setInt("modulex", $cos[0]);
                $nbt->setInt("moduley", $cos[1]);
                $nbt->setInt("modulez", $cos[2]);
            }
        }

        if($p->y < 5){
            $p->sendMessage(JumpLeague::getMessage("savefromvoid"));
            $p->teleport(new Vector3($nbt->getInt("modulex"), $nbt->getInt("moduley"), $nbt->getInt("modulez")));
        }
    }

    public function onDeath(PlayerDeathEvent $event)
    {
        $p = $event->getPlayer();
        $gameid = Games::isInGame($p);
        if(!$gameid) return;
        $game = Games::$games[$gameid];
        if(!$game["online"]) return;

        if(!$game["incombat"]){
            $nbt = $p->namedtag;
            $p->sendMessage(JumpLeague::getMessage("savefromvoid"));
            $p->teleport(new Position($nbt->getInt("modulex"), $nbt->getInt("moduley"), $nbt->getInt("modulez")));
            return;
        }

        if (($key = array_search($p->getName(), $game["players"])) !== false) {
            foreach(Games::$games[$gameid]["players"] as $survivor){
                $survivor = JumpLeague::getMain()->getServer()->getPlayerExact($survivor);
                if($survivor instanceof Player) {
                    $survivor->sendMessage(JumpLeague::getMessage("playerdeadannouncement", ["{player}" => $p->getName()]));
                    if(count($game["players"]) - 1 !== 1){
                        $survivor->sendMessage(JumpLeague::getMessage("playerdeadlast", ["{last}" => count($game["players"]) - 1]));
                    }
                }
            }
            unset(Games::$games[$gameid]["players"][$key]); //Remove the player name in the array
            array_push(Games::$games[$gameid]["spectators"], $p->getName());
        }

        if(count(Games::$games[$gameid]["players"]) <= 1){
            Games::endGame($gameid);
        }
    }

    public function onRespawn(PlayerRespawnEvent $event)
    {
        $p = $event->getPlayer();
        $name = $p->getName();
        foreach (Games::getAllGames() as $key => $game) {
            if (in_array($name, $game["spectators"])) {
                $gameid = $key;
            }
        }
        if(!isset($gameid)) {
            $p->getInventory()->clearAll();
            $sword = Item::get(Item::DIAMOND_SWORD);
            $sword->setCustomName("§bJumpLeague");
            $sword->setNamedTagEntry(new ByteTag("jumpleague", 1));
            $p->getInventory()->setItem(3, $sword);
            return;
        }

        $game = Games::$games[$gameid];
        if(!$game["online"]) return;

        if(!$game["incombat"]){
            $nbt = $p->namedtag;
            $p->sendMessage(JumpLeague::getMessage("savefromvoid"));
            $p->teleport(new Vector3($nbt->getInt("modulex"), $nbt->getInt("moduley"), $nbt->getInt("modulez")));
            return;
        }

        if (($key = array_search($p->getName(), $game["spectators"])) !== false) {
            Games::setSpecPlayer($event->getPlayer());
            $coords = JumpLeague::$coords->get("game" . $gameid)["combat"][$p->namedtag->getInt("num")];
            $event->setRespawnPosition(new Position($coords["x"], $coords["y"], $coords["z"], JumpLeague::getMain()->getServer()->getLevelByName(JumpLeague::$settings->get("game" . $gameid))));
        }
    }
}