<?php

namespace AmitxD\OmniQuest\Manager;

use AmitxD\OmniQuest\OmniQuest;
use pocketmine\player\Player;

class CacheManager {
    private static array $questCache = [];
    private static array $userCache = [];
    private static array $progressCache = [];
    private static ?array $categoriesCache = null;
    private static ?array $questsListCache = null;
    private const CACHE_TTL = 60;

    public static function init(array $categories, array $quests): void {
        self::$categoriesCache = $categories;
        self::$questsListCache = $quests;
    }

    public static function getCachedQuests(): ?array {
        return self::$questsListCache;
    }

    public static function getCachedCategories(): ?array {
        return self::$categoriesCache;
    }

    public static function getQuestInfo(string $questName): ?array {
        return self::$questsListCache[$questName] ?? null;
    }

    public static function getCachedUserData(string $player): ?array {
        if (!isset(self::$userCache[$player]) || self::isCacheExpired(self::$userCache[$player]['timestamp'])) {
            $data = DatabaseManager::getUserData($player);
            if ($data !== null) {
                self::$userCache[$player] = [
                    'data' => $data,
                    'timestamp' => time()
                ];
            }
        }
        return self::$userCache[$player]['data'] ?? null;
    }

    public static function getCachedProgress(string $player, string $quest): int|string|null {
        $cacheKey = $player . '_' . $quest;
        if (!isset(self::$progressCache[$cacheKey]) || self::isCacheExpired(self::$progressCache[$cacheKey]['timestamp'])) {
            $data = DatabaseManager::getUserQuestData($player, $quest);
            if ($data !== null) {
                self::$progressCache[$cacheKey] = [
                    'progress' => $data['progress'],
                    'timestamp' => time()
                ];
            }
        }
        return self::$progressCache[$cacheKey]['progress'] ?? null;
    }

    public static function updateProgress(string $player, string $quest, int|string $progress): void {
        $cacheKey = $player . '_' . $quest;
        self::$progressCache[$cacheKey] = [
            'progress' => $progress,
            'timestamp' => time()
        ];
    }

    public static function updateUserData(string $player, array $data): void {
        self::$userCache[$player] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }

    public static function clearPlayerCache(string $player): void {
        unset(self::$userCache[$player]);
        foreach (self::$progressCache as $key => $value) {
            if (strpos($key, $player . '_') === 0) {
                unset(self::$progressCache[$key]);
            }
        }
    }

    public static function clearAllCache(): void {
        self::$userCache = [];
        self::$progressCache = [];
    }

    private static function isCacheExpired(int $timestamp): bool {
        return (time() - $timestamp) > self::CACHE_TTL;
    }
} 