<?php

namespace OmniQuest\Manager;

use OmniQuest\Manager\FormManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class QuestCommand extends Command
{

    public function __construct() {
        $command_name = "quest";
        $command_description = "open quest ui";
        parent::__construct($command_name, $command_description, "/".$command_name);
    }
    public function execute(CommandSender $player, string $commandLabel, array $args) {
        if ($player instanceof Player) {
            $formM = new FormManager();
            $formM->QuestForm($player);
        } else {
            $player->sendMessage("§cPlease Use this command in game");
        }
    }
}