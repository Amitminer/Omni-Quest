<?php

declare(strict_types = 1);

namespace AmitxD\OmniQuest;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use AmitxD\OmniQuest\commands\QuestCommand;
use AmitxD\OmniQuest\Manager\QuestManager;
use AmitxD\OmniQuest\Manager\EventManager;
use AmitxD\OmniQuest\Manager\DatabaseManager;
use AmitxD\OmniQuest\Manager\CacheManager;
use AmitxD\OmniQuest\scorehud\ScoreHudAddon;

class OmniQuest extends PluginBase
{

    private $config;
    private $categories = [];
    private $quests;
    private $questManager;
    public static $instance;

    public function onLoad() : void
    {
        self::$instance = $this;
    }

    public function onEnable() : void
    {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        if (!file_exists($this->getDataFolder()."data.db")) $this->saveResource("data.db");
        $this->initQuests();
        $this->questManager = new QuestManager();
        $scoreHud = new ScoreHudAddon($this);
        $this->getServer()->getPluginManager()->registerEvents(new EventManager($this), $this);
        $this->getServer()->getCommandMap()->register("quest", new QuestCommand($this, "quest"));
    }

    public function onDisable() : void
    {
        DatabaseManager::closeDb();
        CacheManager::clearAllCache();
    }

    public function initQuests(): void {
        $config = $this->config;
        $quests = $this->quests;

        foreach ($config->get("categories") as $category) {
            foreach ($category as $key => $value) {
                $this->quests[$key] = $value;
            }
        }
        foreach ($config->getNested("categories") as $category => $value) {
            $quests[$category] = array();
            foreach (array_keys($value) as $categoryValue) {
                array_push($quests[$category], $categoryValue);
            }
        }
        $this->categories = $quests;
        
        // Initialize cache with quest data
        CacheManager::init($this->categories, $this->quests);
        DatabaseManager::initDb();
    }

    public static function getInstance() : self {
        return self::$instance;
    }

    public function getCategories() {
        return CacheManager::getCachedCategories() ?? $this->categories;
    }

    public function getQuests() {
        return CacheManager::getCachedQuests() ?? $this->quests;
    }

    public function getQuestManager(): QuestManager {
        return $this->questManager;
    }

    public function getQuestConfig() : Config {
        return $this->config;
    }
}