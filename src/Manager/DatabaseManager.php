<?php

namespace AmitxD\OmniQuest\Manager;

use AmitxD\OmniQuest\OmniQuest;

class DatabaseManager
{
    private static ?OmniQuest $plugin = null;
    private static ?\SQLite3 $db = null;

    private static function getPlugin(): OmniQuest
    {
        return self::$plugin ??= OmniQuest::getInstance();
    }

    public static function getDb(): \SQLite3
    {
        if (self::$db === null) {
            self::$db = new \SQLite3(self::getPlugin()->getDataFolder() . "data.db");
        }
        return self::$db;
    }

    public static function initDb(): void
    {
        $db = self::getDb();
        $queries = ["CREATE TABLE IF NOT EXISTS users(name VARCHAR(255), current TEXT)"];
        foreach (self::getPlugin()->getQuests() as $name => $value) {
            $queries[] = "CREATE TABLE IF NOT EXISTS $name (user VARCHAR(255), progress INT)";
        }
        foreach ($queries as $query) {
            $db->exec($query);
        }
    }

    public static function closeDb(): void
    {
        if (self::$db !== null) {
            self::$db->close();
            self::$db = null;
        }
    }

    public static function getUserData(string $player): ?array
    {
        $stmt = self::getDb()->prepare("SELECT * FROM users WHERE name = :name");
        $stmt->bindValue(":name", $player, SQLITE3_TEXT);
        return $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    public static function getUserQuestData(string $player, string $quest): ?array
    {
        $stmt = self::getDb()->prepare("SELECT * FROM $quest WHERE user = :player");
        $stmt->bindValue(":player", $player, SQLITE3_TEXT);
        return $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    public static function registerUser(string $name): void
    {
        $db = self::getDb();
        $quests = self::getPlugin()->getQuests();

        foreach ($quests as $questName => $value) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM $questName WHERE user = :name");
            $stmt->bindValue(":name", $name, SQLITE3_TEXT);
            $res = $stmt->execute()->fetchArray()[0] ?? 0;

            if ($res <= 0) {
                $db->exec("INSERT INTO $questName(user, progress) VALUES ('$name', 0)");
            }
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE name = :name");
        $stmt->bindValue(":name", $name, SQLITE3_TEXT);
        $res = $stmt->execute()->fetchArray()[0] ?? 0;

        if ($res <= 0) {
            $db->exec("INSERT INTO users(name, current) VALUES ('$name', NULL)");
        }
    }

    public static function setCompleted(string $player, string $quest): void
    {
        self::getDb()->exec("UPDATE $quest SET progress = 'FINISHED' WHERE user = '$player'");
    }

    public static function setCurrent(string $player, string $quest): void
    {
        self::getDb()->exec("UPDATE users SET current = '$quest' WHERE name = '$player'");
    }

    public static function resetQuest(string $player): void
    {
        self::getDb()->exec("UPDATE users SET current = NULL WHERE name = '$player'");
    }

    public static function getProgress(string $player, string $quest): int
    {
        $data = self::getUserQuestData($player, $quest);
        return $data["progress"] ?? 0;
    }

    public static function incrementProgress(string $player, string $quest): void
    {
        $progress = self::getProgress($player, $quest);
        self::getDb()->exec("UPDATE $quest SET progress = " . ($progress + 1) . " WHERE user = '$player'");
    }
} 