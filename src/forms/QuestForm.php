<?php

namespace AmitxD\OmniQuest\forms;

use AmitxD\OmniQuest\ {
    OmniQuest,
    Manager\QuestManager
};

use pocketmine\player\Player;

use AmitxD\OmniQuest\libs\FormAPI\SimpleForm;

class QuestForm extends SimpleForm
{

    public function __construct(?callable $callable, Player $player, int $category){
        parent::__construct($callable);
        $categoryName = QuestManager::getCategory($category);
        $categories = OmniQuest::getInstance()->getCategories();
        foreach ($categories as $cat => $quests) {
            foreach (array_values($quests) as $id => $questName) {
                if ($cat === $categoryName) {
                    $questName = QuestManager::getQuestNameById($id, $category);
                    $questData = QuestManager::getQuestInfoById($category, $questName);

                    if (QuestManager::isCurrent($player->getName(), $questName)) {
                        $statut = OmniQuest::getInstance()->getQuestConfig()->get("quest-inprogress");
                    } elseif (QuestManager::isCompleted($player->getName(), $questName)) {
                        $statut = OmniQuest::getInstance()->getQuestConfig()->get("quest-finished");
                    } else {
                        $statut = OmniQuest::getInstance()->getQuestConfig()->get("quest-opened");
                    }

                    $this->addButton($questData["name"] . "\n" . $statut, 0);
                }
            }
        }
        if (!is_null(OmniQuest::getInstance()->getQuestConfig()->get("category-select-title"))) {
            $this->setTitle(OmniQuest::getInstance()->getQuestConfig()->get("category-select-title"));
        }
        if (!is_null(OmniQuest::getInstance()->getQuestConfig()->get("quest-select-content"))) {
            $this->setContent(OmniQuest::getInstance()->getQuestConfig()->get("quest-select-content"));
        }
    }
}