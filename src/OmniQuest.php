<?php

declare(strict_types = 1);

namespace AmitxD\OmniQuest;

use AmitxD\OmniQuest\Manager\FormManager;
use AmitxD\OmniQuest\Manager\MessageManager;
use AmitxD\OmniQuest\Manager\CategoryManager;
use AmitxD\OmniQuest\Commands\CategoryCommands;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class OmniQuest extends PluginBase {

    protected static $instance;

    protected function onLoad(): void {
        self::$instance = $this;
    }

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        $this->loadCategories();
    }

    private function loadCategories(): void {
        $config = new Config($this->getDataFolder() . "categories.yml", Config::YAML);
        $categories = $config->get("categories", []);
        CategoryManager::getInstance()->setCategories($categories);
    }

    protected function onDisable(): void {}

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MessageManager::NOCONSOLE);
            return false;
        }
        switch ($cmd->getName()) {
            case "quest":
                FormManager::QuestCategoryForm($sender);
                break;
            case "questcategory":
                CategoryCommands::handleCommand($sender, $cmd, $label, $args);
                break;
        }
        return true;
    }

    public function getConfigValue(string $name) {
        $config = $this->getConfig();
        return $config->get($name);
    }

    public function setConfigValue(string $name, $value): bool {
        $config = $this->getConfig();
        $config->set($name, $value);
        return $config->save();
    }

    public static function getInstance(): self {
        return self::$instance;
    }
}