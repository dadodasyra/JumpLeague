<?php


namespace dadodasyra\commands;

use dadodasyra\JumpLeague;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class Commands extends BaseCommand
{

    public static $loottable = [];
    public static $chests = [];

    public function __construct()
    {
        parent::__construct("jl", "Menu d'aide du plugin JumpLeague", "/jl help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender->hasPermission("jl")) {
            $sender->sendMessage(self::NO_PERMISSION);
            return false;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage(self::NO_PLAYER);
            return false;
        }
        if (!isset($args[0])) {
            $args[0] = "help";
        }
        switch ($args[0]) {
            case "spawn":
                $gameid = $this->getGame($sender);
                if (!$this->testarg($sender, $gameid)) return false;
                $this->spawn($sender, $gameid);
                break;

            /*case "module":
                $gameid = $this->getGame($sender);
                if (!$this->testarg($sender, $gameid)) return false;
                if (!isset($args[1]) || !$args[1] > 1 && !$args[1] <= 3) {
                    $sender->sendMessage(self::PREF . "§cVeuillez indiquez le numéro de module");
                    return false;
                }
                $this->module($sender, $args, $gameid);
                break;*/
            case "combat":
                $gameid = $this->getGame($sender);
                if(!$this->testarg($sender, $gameid)) return false;
                if (!isset($args[1]) || !$args[1] > 0 || !$args[1] >= 8) {
                    $sender->sendMessage(self::PREF . '§cVeuillez indiquer le numéro de "joueur", il doit se trouver entre 1 et 8');
                    return false;
                }
                $this->combat($sender, $args, $gameid);
                break;

            case "arrive":
                $gameid = $this->getGame($sender);
                if (!$this->testarg($sender, $gameid)) return false;
                $this->arrive($sender, $gameid);
                break;

            case "loottable":
                if(!isset($args[1])){
                    $sender->sendMessage("Veuillez indiquez, stop ou start");
                    return false;
                }
                $this->loottable($sender, $args);
               break;

            case "chest":
                if(!isset($args[1])){
                    $sender->sendMessage("Veuillez indiquez, stop ou start");
                    return false;
                }
                $this->chests($sender, $args);
                break;

            default:
                $this->help($sender);
                break;
        }

        return true;
    }

    public function help(Player $sender)
    {
        $sender->sendMessage(self::PREF . "§aVoici la liste de commandes admin disponible pour JumpLeague :");
        $sender->sendMessage(self::PREF . "§bspawn §a- Set le spawn initial de départ");
        /*$sender->sendMessage(self::PREF . "§bmodule <num du module> §a- Set les checkpoint pour chaque partie");*/
        $sender->sendMessage(self::PREF . "§barrive §a- Set les cordonnés d'arrivés de chaque parties");
        $sender->sendMessage(self::PREF . "§bcombat <num de joueur, 1 à 8> §a- Set les cordonnées d'un spawn de combat");
        $sender->sendMessage(self::PREF . "§bloottable <start OU stop> §a- Set les cordonnées de coffre d'exemple de loot, s'active avec start, s'arrete avec stop");
        $sender->sendMessage(self::PREF . "§bchests <start OU stop> §a- Set les cordonnées d'un coffre a remplir de loot, s'active avec start, s'arrete avec stop");
    }

    public function spawn(Player $p, int $gameid)
    {
        $conf = JumpLeague::$coords->get("game".$gameid);
        $conf["spawn"] = [
            "x" => $p->x,
            "y" => $p->y,
            "z" => $p->z
        ];
        JumpLeague::$coords->set("game" . $gameid, $conf);
        JumpLeague::$coords->save();

        $p->sendMessage(self::PREF . "§aLes coordonnés du spawn pour la game n°§b".$gameid." §aont bien été mise sur §bX: " . $p->x . " Y: " . $p->y . " Z: " . $p->z);
        $p->sendMessage(self::PREF . "§aLes coordonnés du 2 ème jump doivent être donc en §bX: " . ($p->x + 100) . " Y: " . $p->y . " Z: " . $p->z);
    }

    /*public function module(Player $p, array $args, int $gameid)
    {
        $conf = JumpLeague::$coords->get("game".$gameid);
        $conf["modules"][$args[1]] = [
            "x" => $p->x,
            "y" => $p->y,
            "z" => $p->z,
            "num" => $args[1]
        ];
        JumpLeague::$coords->set("game" . $gameid, $conf);
        JumpLeague::$coords->save();


        $p->getLevel()->addParticle(new FloatingTextParticle($p, JumpLeague::getMessage("modulestext", ["{num}" => $args[1], "{partie}" => $gameid], 1)));
        $p->sendMessage(self::PREF . "§aLe texte du module n°§b".$args[1]." §apour la partie n°§b".$gameid." §aa été créer");
    }*/

    public function arrive(Player $p, int $gameid)
    {
        $conf = JumpLeague::$coords->get("game".$gameid);
        $conf["arrive"] = [
            "x" => $p->getFloorX(),
            "y" => $p->getFloorY(),
            "z" => $p->getFloorZ()
        ];
        JumpLeague::$coords->set("game" . $gameid, $conf);
        JumpLeague::$coords->save();

        $p->sendMessage(self::PREF . "§aLes coordonnés de l'arrivé pour la game n°§b".$gameid." §aont bien été mise sur §bX: " . $p->x . " Y: " . $p->y . " Z: " . $p->z);
        $p->sendMessage(self::PREF . "§aLes coordonnés de l'arrivé du 2 ème jump doivent être donc en §bX: " . ($p->x + 100) . " Y: " . $p->y . " Z: " . $p->z);
    }

    public function combat(Player $p, array $args, int $gameid)
    {
        $conf = JumpLeague::$coords->get("game".$gameid);
        $conf["combat"][$args[1] - 1] = [
            "x" => $p->x,
            "y" => $p->y,
            "z" => $p->z
        ];
        JumpLeague::$coords->set("game".$gameid, $conf);
        JumpLeague::$coords->save();

        $p->sendMessage(self::PREF . "§aLes coordonnés du spawn du joueur n°§b".$args[1]." §apour la game n°§b".$gameid." §aont bien été mise sur §bX: " . $p->x . " Y: " . $p->y . " Z: " . $p->z);
    }

    public function loottable(Player $sender, array $args)
    {
        if($args[1] === "stop"){
            unset(self::$loottable[$sender->getName()]);
            $sender->sendMessage(self::PREF . "§cVotre status a bien été mis sur stop pour loottable");
        } else if ($args[1] === "start"){
            self::$loottable[$sender->getName()] = true;
            $sender->sendMessage(self::PREF . "§cVotre status a bien été mis sur start pour loottable");
        } else {
            $sender->sendMessage(self::PREF . "§cEntrée invalide, stop ou start");
        }
    }

    public function chests(Player $sender, array $args)
    {
        if($args[1] === "stop"){
            unset(self::$chests[$sender->getName()]);
            $sender->sendMessage(self::PREF . "§cVotre status a bien été mis sur stop pour chest");
        } else if ($args[1] === "start"){
            self::$chests[$sender->getName()] = true;
            $sender->sendMessage(self::PREF . "§cVotre status a bien été mis sur start pour chest");
        } else {
            $sender->sendMessage(self::PREF . "§cEntrée invalide, stop ou start");
        }
    }

    public function testarg(Player $sender, int $gameid)
    {
        if (!isset($gameid) || !$gameid > 1 && !$gameid <= 3) {
            $sender->sendMessage(self::PREF . "§cVeuillez indiquez le numéro de partie");
            return false;
        }
        return true;
    }

    public static function getGame(Player $sender) : int
    {
        $listgames = [JumpLeague::$settings->get("game1"), JumpLeague::$settings->get("game2"), JumpLeague::$settings->get("game3")];
        if(!in_array($sender->getLevel()->getName(), $listgames)) return 0;
        return array_search($sender->getLevel()->getName(), $listgames) + 1;
    }
}