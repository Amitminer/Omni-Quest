<?php

namespace OmniQuest;

use OmniQuest\Manager\QuestCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase
{
    public static Main $main;
    
    protected function onLoad(): void
    {
        self::$main = $this;
    }
    public function onEnable(): void
    {
        $this->getLogger()->info("OmniQuest Enabled!");
        $this->getServer()->getCommandMap()->register("quest", new QuestCommand());
    }
}