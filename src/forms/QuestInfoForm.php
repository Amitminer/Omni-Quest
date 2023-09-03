<?php

namespace AmitxD\OmniQuest\forms;

use AmitxD\OmniQuest\{
	OmniQuest,
	Manager\QuestManager
};

use pocketmine\player\Player;

use AmitxD\OmniQuest\libs\FormAPI\ModalForm;

class QuestInfoForm extends ModalForm
{

	public function __construct(?callable $callable, Player $player, int $categoryId, int $questId){
		parent::__construct($callable);

		$questName = QuestManager::getQuestNameById($questId, $categoryId);
		$questData = QuestManager::getQuestInfoById($categoryId, $questName);

		$this->setTitle($questData["name"]);
		$this->setContent($questData["description"]);

		if(QuestManager::isCurrent($player->getName(), $questName)){
			$this->setButton1(OmniQuest::getInstance()->getQuestConfig()->get("button-info-pause"));
		} else {
			$this->setButton1(OmniQuest::getInstance()->getQuestConfig()->get("button-info-start"));
		}
		if(!is_null(OmniQuest::getInstance()->getQuestConfig()->get("button-leave"))){
			$this->setButton2(OmniQuest::getInstance()->getQuestConfig()->get("button-leave"));
		}
	}
}