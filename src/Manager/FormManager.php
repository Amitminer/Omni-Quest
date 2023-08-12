<?php

declare(strict_types = 1);

namespace AmitxD\OmniQuest\Manager;

use pocketmine\player\Player;
use OmniLibs\libs\jojoe77777\FormAPI\SimpleForm;

class FormManager {

    public function __construct() {
        // NOOP (No operation)
    }

    public static function QuestCategoryForm(Player $player): void {
        $form = new SimpleForm(function(Player $player, $data) {
            if ($data === null) {
                return true;
            }
            CategoryManager::getInstance()->handleCategorySelection($player, $data);
        });

        $form->setTitle("Quest Category");
        $categories = CategoryManager::getInstance()->getCategories();
        foreach ($categories as $category) {
            $form->addButton($category);
        }
        $player->sendForm($form);
    }
}