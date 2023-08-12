<?php

namespace AmitxD\OmniQuest\Manager;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use AmitxD\OmniQuest\OmniQuest;

class CategoryManager {

    private static $instance;
    private $categories = [];
    private $config;

    private function __construct() {
        $this->config = new Config(OmniQuest::getInstance()->getDataFolder() . "categories.yml", Config::YAML);
        $this->loadCategories();
    }

    public static function getInstance(): self {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadCategories(): void {
        $this->categories = $this->config->get("categories", []);
    }

    public function setCategories(array $categories): void {
        $this->categories = $categories;
    }

    public function getCategories(): array {
        return $this->categories;
    }

    public function addCategory(string $categoryName): bool {
        if (in_array($categoryName, $this->categories)) {
            return false; // Category already exists
        }

        $this->categories[] = $categoryName;
        $this->saveCategories();
        return true; // Category added successfully
    }

    public function removeCategory(string $categoryName): bool {
        $index = array_search($categoryName, $this->categories);
        if ($index === false) {
            return false; // Category doesn't exist
        }

        array_splice($this->categories, $index, 1);
        $this->saveCategories();
        return true; // Category removed successfully
    }

    private function saveCategories(): void {
        
        $this->config->set("categories", $this->categories);
        $this->config->save();
    }

    public function handleCategorySelection(Player $player, int $selectedIndex): void {
        $selectedCategory = $this->categories[$selectedIndex];
        $player->sendMessage("You selected the category: $selectedCategory");
    }
}