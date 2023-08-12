<?php

namespace AmitxD\OmniQuest\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use AmitxD\OmniQuest\Manager\CategoryManager;
use AmitxD\OmniQuest\Manager\MessageManager;

class CategoryCommands {

    public static function handleCommand(CommandSender $sender, Command $command, string $label, array $args): bool {

        if (count($args) < 1) {
            $sender->sendMessage(MessageManager::CATEGORY_USAGE);
            return false;
        }

        switch ($args[0]) {
            case "list":
                $categories = CategoryManager::getInstance()->getCategories();
                $sender->sendMessage("Quest Categories: " . implode(", ", $categories));
                break;
            case "add":
                if (count($args) !== 2) {
                    $sender->sendMessage("Usage: /quest category add <category>");
                    return false;
                }

                $categoryName = $args[1];
                $manager = CategoryManager::getInstance();

                if ($manager->addCategory($categoryName)) {
                    $sender->sendMessage("Category added: $categoryName");
                } else {
                    $sender->sendMessage("Category already exists.");
                }
                break;
            case "remove":
                if (count($args) !== 2) {
                    $sender->sendMessage("Usage: /quest category remove <category>");
                    return false;
                }

                $categoryName = $args[1];
                $manager = CategoryManager::getInstance();

                if ($manager->removeCategory($categoryName)) {
                    $sender->sendMessage("Category removed: $categoryName");
                } else {
                    $sender->sendMessage("Category not found.");
                }
                break;
            default:
                $sender->sendMessage(MessageManager::CATEGORY_USAGE);
                break;
        }
        return true;
    }
}