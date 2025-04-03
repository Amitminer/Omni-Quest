<?php

namespace AmitxD\OmniQuest\Manager;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\Server;
use AmitxD\OmniQuest\OmniQuest;
use OmniCore\lib\davidglitch04\libEco\libEco;

class QuestManager
{
    private static ?OmniQuest $plugin = null;

    private static function getPlugin(): OmniQuest
    {
        return self::$plugin ??= OmniQuest::getInstance();
    }

    public static function getQuestInfoById(int $id, string $name)
    {
        $categories = array_values(self::getPlugin()->getCategories());
        $quests = self::getPlugin()->getQuests();
        return isset($categories[$id]) && in_array($name, $categories[$id]) ? $quests[$name] : null;
    }

    public static function getQuestInfo(string $name)
    {
        return CacheManager::getQuestInfo($name);
    }

    public static function getQuestNameById(int $questId, int $categoryId)
    {
        $categories = array_values(self::getPlugin()->getCategories());
        return $categories[$categoryId][$questId] ?? null;
    }

    public static function getCategoriesName(): array
    {
        return array_keys(self::getPlugin()->getCategories());
    }

    public static function getCategory(int $id)
    {
        $categories = self::getCategoriesName();
        return $categories[$id] ?? null;
    }

    public static function getUserData(string $player)
    {
        return CacheManager::getCachedUserData($player);
    }

    public static function getUserQuestData(string $player, string $quest)
    {
        $progress = CacheManager::getCachedProgress($player, $quest);
        return $progress !== null ? ['progress' => $progress] : null;
    }

    public static function setCompleted(string $player, string $quest): void
    {
        DatabaseManager::setCompleted($player, $quest);
        CacheManager::updateProgress($player, $quest, "FINISHED");
        CacheManager::clearPlayerCache($player); // Clear user cache as quest status changed
        
        // Get player instance and quest data
        $playerInstance = Server::getInstance()->getPlayerExact($player);
        if ($playerInstance instanceof Player) {
            $questData = self::getQuestInfo($quest);
            if ($questData) {
                // Send completion title
                $playerInstance->sendTitle(
                    "§6✦ §aQuest Completed! §6✦",
                    "§f" . $questData["name"],
                    20,
                    60,
                    20
                );
                
                // Send popup message
                $playerInstance->sendPopup("§e✦ §fCompleted Quest: §a" . $questData["name"] . " §e✦");
                
                // Handle money reward first
                if (isset($questData["money"]) && is_numeric($questData["money"])) {
                    $money = (int)$questData["money"];
                    if ($money > 0) {
                    libEco::addMoney($playerInstance, $money);
                        $playerInstance->sendMessage("§a+ §e$" . number_format($money) . " §7(Quest Reward)");
                    }
                }
                
                // Send rewards message if there are any rewards
                $hasRewards = (isset($questData["money"]) && $questData["money"] > 0) || 
                             (isset($questData["rewards"]) && !empty($questData["rewards"]));
                
                if ($hasRewards) {
                    $rewardsText = "§eRewards received:§r\n";
                    if (isset($questData["money"]) && $questData["money"] > 0) {
                        $rewardsText .= "§7- §e$" . number_format($questData["money"]) . " §7coins\n";
                    }
                    
                    if (isset($questData["rewards"]) && is_array($questData["rewards"])) {
                        foreach ($questData["rewards"] as $command) {
                            if (strpos($command, "give") !== false) {
                                $rewardsText .= "§7- " . str_replace(["/give {PLAYER} ", ":"], ["", " "], $command) . "\n";
                            }
                        }
                    }
                    $playerInstance->sendMessage($rewardsText);
                }
                
                // Execute command rewards if they exist
                if (isset($questData["rewards"]) && is_array($questData["rewards"])) {
                    foreach ($questData["rewards"] as $command) {
                        Server::getInstance()->dispatchCommand(
                            new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()),
                            str_replace("{PLAYER}", $player, $command)
                        );
                    }
                }
            }
        }
    }

    public static function isCompleted(string $player, string $quest): bool
    {
        $progress = CacheManager::getCachedProgress($player, $quest);
        return $progress === "FINISHED";
    }

    public static function getProgress(string $player, string $quest): int
    {
        $progress = CacheManager::getCachedProgress($player, $quest);
        if ($progress === "FINISHED" || !is_numeric($progress)) {
            return 0;
        }
        return (int)$progress;
    }

    public static function incrementProgress(Player $player, string $quest): void
    {
        $name = $player->getName();
        $questData = self::getQuestInfo($quest);

        if (!$questData) return;

        $currentProgress = self::getProgress($name, $quest);
        $newProgress = $currentProgress + 1;
        
        DatabaseManager::incrementProgress($name, $quest);
        CacheManager::updateProgress($name, $quest, $newProgress);

        // Show progress popup
        $total = $questData["number"];
        $progressPercent = round(($newProgress / $total) * 100, 1);
        $progressBar = str_repeat("█", (int)($progressPercent/10)) . str_repeat("▒", 10 - (int)($progressPercent/10));
        $player->sendPopup("§e" . $questData["name"] . "\n§7" . $progressBar . " §f" . $progressPercent . "%");

        if ($newProgress >= $questData["number"]) {
            self::setCompleted($name, $quest);
            self::resetQuest($name);
        }
    }

    public static function updateQuest(Player $player, int $category, string $quest): void
    {
        $name = $player->getName();
        $usrData = self::getUserData($name);
        $questData = self::getQuestInfoById($category, $quest);

        if (!$questData) return;

        if ($usrData["current"] === $quest) {
            self::resetQuest($name);
            $player->sendMessage(str_replace("{QUEST}", $questData["name"], self::getPlugin()->getQuestConfig()->get("paused-quest")));
        } else {
            self::setCurrent($name, $quest);
            $player->sendMessage(str_replace("{QUEST}", $questData["name"], self::getPlugin()->getQuestConfig()->get("started-quest")));
            
            // Show instructions for command-based quests
            if (isset($questData["type"]) && $questData["type"] === "command") {
                // $player->sendTitle(
                //     "§6Quest Instructions",
                //     "§fUse: §e/" . $questData["command"],
                //     10,
                //     60,
                //     10
                // );
                $player->sendMessage("\n§6Quest Instructions:\n§7" . $questData["description"]);
            }
        }
    }

    public static function registerUser(string $name): void
    {
        DatabaseManager::registerUser($name);
    }

    public static function setCurrent(string $player, string $quest): void
    {
        DatabaseManager::setCurrent($player, $quest);
        CacheManager::clearPlayerCache($player); // Clear cache as current quest changed
    }

    public static function resetQuest(string $player): void
    {
        DatabaseManager::resetQuest($player);
        CacheManager::clearPlayerCache($player); // Clear cache as quest was reset
    }

    public static function isCurrent(string $player, string $name): bool
    {
        $data = CacheManager::getCachedUserData($player);
        return isset($data["current"]) && $data["current"] === $name;
    }

    public static function initDb(): void
    {
        DatabaseManager::initDb();
    }

    public static function closeDb(): void
    {
        DatabaseManager::closeDb();
        CacheManager::clearAllCache();
    }
}
