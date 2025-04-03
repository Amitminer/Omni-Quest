<?php

declare(strict_types = 1);

namespace AmitxD\OmniQuest\scorehud;

use AmitxD\OmniQuest\OmniQuest;
use AmitxD\OmniQuest\Manager\QuestManager;
use Ifera\ScoreHud\event\PlayerTagUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
/**
 * @phpstan-type PlayerCacheItem array
 */
class ScoreHudAddon {

    protected OmniQuest $plugin;
    
    /** @var array<string, array{
     *     userData: ?array,
     *     questData: ?array,
     *     progress: int,
     *     lastUpdate: int
     * }> */
    private array $playerCache = [];
    private const CACHE_TTL = 60; // 60 seconds to match update duration

    public function __construct(OmniQuest $plugin) {
        $this->plugin = $plugin;
        $this->registerEvents();
        $this->onLoad();
    }

    public function registerEvents(): void {
        $this->plugin->getServer()->getPluginManager()->registerEvents(new ScoreHudListener($this), $this->plugin);
    }

    public function onLoad(): void {
        $this->repeat(function() {
            $currentPlayers = $this->plugin->getServer()->getOnlinePlayers();
            
            /** @var array<string, array<string, string|int>> */
            $updates = [];
            
            foreach ($currentPlayers as $player) {
                if (!$player->isOnline()) {
                    continue;
                }
                
                $playerName = $player->getName();
                $this->updateCache($playerName);
                
                $updates[$playerName] = [
                    ScoreHudTags::CURRENT_QUEST_NAME => $this->getCurrentQuest($playerName),
                    ScoreHudTags::QUEST_PROGRESS => $this->getQuestProgress($playerName),
                    ScoreHudTags::QUEST_TOTAL => $this->getQuestTotal($playerName),
                    ScoreHudTags::QUEST_PERCENT => $this->getQuestPercent($playerName),
                    ScoreHudTags::QUEST_TYPE => $this->getQuestType($playerName)
                ];
            }
            
            foreach ($updates as $playerName => $data) {
                $player = $this->plugin->getServer()->getPlayerExact($playerName);
                if ($player === null) continue;
                
                foreach ($data as $tag => $value) {
                    if (class_exists(PlayerTagUpdateEvent::class)) {
                        (new PlayerTagUpdateEvent($player, new ScoreTag($tag, (string)$value)))->call();
                    }
                }
            }
        }, $this->getUpdateDuration());
    }

    private function updateCache(string $playerName): void {
        $currentTime = time();
        
        // If cache is still valid, skip update
        if (isset($this->playerCache[$playerName]) && 
            ($currentTime - $this->playerCache[$playerName]["lastUpdate"]) < self::CACHE_TTL) {
            return;
        }

        // Get user data from database (single query)
        $userData = QuestManager::getUserData($playerName);
        
        // Initialize cache entry
        $this->playerCache[$playerName] = [
            "userData" => $userData,
            "questData" => null,
            "progress" => 0,
            "lastUpdate" => $currentTime
        ];

        // If there's an active quest, get its data and progress (at most 2 queries)
        if ($userData !== null && isset($userData["current"])) {
            $currentQuest = $userData["current"];
            $this->playerCache[$playerName]["questData"] = QuestManager::getQuestInfo($currentQuest);
            if ($currentQuest !== null) {
                $this->playerCache[$playerName]["progress"] = QuestManager::getProgress($playerName, $currentQuest);
            }
        }
    }

    public function getCurrentQuest(string $playerName): string {
        $cache = $this->playerCache[$playerName] ?? null;
        if (!$cache || !$cache["questData"]) {
            return "None";
        }
        return $cache["questData"]["name"];
    }

    public function getQuestProgress(string $playerName): string {
        $cache = $this->playerCache[$playerName] ?? null;
        if (!$cache || !$cache["questData"] || !isset($cache["questData"]["number"])) {
            return "0";
        }
        return (string)$cache["progress"];
    }

    public function getQuestTotal(string $playerName): string {
        $cache = $this->playerCache[$playerName] ?? null;
        if (!$cache || !$cache["questData"]) {
            return "0";
        }
        return (string)($cache["questData"]["number"] ?? "1");
    }

    public function getQuestPercent(string $playerName): string {
        $cache = $this->playerCache[$playerName] ?? null;
        if (!$cache || !$cache["questData"]) {
            return "0%";
        }

        if (isset($cache["questData"]["number"])) {
            $progress = $cache["progress"];
            $total = $cache["questData"]["number"];
            return round(($progress / $total) * 100) . "%";
        }

        return "0%";
    }

    public function getQuestType(string $playerName): string {
        $cache = $this->playerCache[$playerName] ?? null;
        if (!$cache || !$cache["questData"]) {
            return "None";
        }
        return ucfirst($cache["questData"]["type"] ?? "unknown");
    }

    public function getUpdateDuration(): int {
        return 60; // 60 seconds
    }

    public function repeat(callable $callback, int $interval): void {
        $scheduler = $this->plugin->getScheduler();
        $scheduler->scheduleRepeatingTask(new ClosureTask($callback), $interval * 20);
    }
}