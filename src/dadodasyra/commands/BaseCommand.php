<?php

declare(strict_types=1);

namespace dadodasyra\commands;

use dadodasyra\JumpLeague;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand{

    public const PREF = "§b[§aJumpLeague§b] ";
    public const NO_PERMISSION = "§cTu n'as pas le droit d'exécuter cette commande";
    public const NO_PLAYER = "§cCette commande dois être executé in-game.";

    public function __construct(string $name, string $description, string $usageMessage = "null", array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public function getPlugin() : Plugin{
        return JumpLeague::getMain();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if(!JumpLeague::getMain()->isEnabled()) return false;
        if(!$this->testPermission($sender)) return false;
        $success = JumpLeague::getMain()->onCommand($sender, $this, $commandLabel, $args);
        if(!$success and $this->usageMessage !== "") throw new InvalidCommandSyntaxException();
        return $success;
    }
}