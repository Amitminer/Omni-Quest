<?php

namespace AmitxD\OmniQuest\forms;

use AmitxD\OmniQuest\{
	OmniQuest,
	Manager\QuestManager
};

use pocketmine\player\Player;

use OmniCore\lib\jojoe77777\FormAPI\CustomForm;

class QuestInfoForm extends CustomForm
{
	public function __construct(?callable $callable, Player $player, int $categoryId, int $questId){
		parent::__construct($callable);

		$questName = QuestManager::getQuestNameById($questId, $categoryId);
		$questData = QuestManager::getQuestInfoById($categoryId, $questName);
		$isCompleted = QuestManager::isCompleted($player->getName(), $questName);
		$isCurrent = QuestManager::isCurrent($player->getName(), $questName);
		
		$this->setTitle($questData["name"]);
		
		$description = $questData["description"];
		
		$hasRewards = (isset($questData["money"]) && $questData["money"] > 0) || 
					 (isset($questData["rewards"]) && !empty($questData["rewards"]));
		
		if ($hasRewards) {
			$description .= "\n\n§eRewards:";
			if (isset($questData["money"]) && $questData["money"] > 0) {
				$description .= "\n§7- §e$" . number_format($questData["money"]) . " §7";
			}
			if (isset($questData["rewards"]) && is_array($questData["rewards"])) {
				foreach ($questData["rewards"] as $command) {
					if (strpos($command, "give") !== false) {
						$description .= "\n§7- " . str_replace(["/give {PLAYER} ", ":"], ["", " "], $command);
					}
				}
			}
		}
		
		$this->addLabel($description);
		
		if (isset($questData["number"])) {
			$progress = QuestManager::getProgress($player->getName(), $questName);
			$total = $questData["number"];
			$progressPercent = ($progress / $total) * 100;
			
			$progressBar = str_repeat("█", (int)($progressPercent/10)) . str_repeat("▒", 10 - (int)($progressPercent/10));
			$this->addLabel("\n§eProgress: §f$progress/$total\n§7$progressBar §f" . number_format($progressPercent, 1) . "%");
		}

		$status = "§7Not Started";
		if ($isCompleted) {
			$status = "§aCompleted";
		} elseif ($isCurrent) {
			$status = "§eIn Progress";
		}
		$this->addLabel("\n§fStatus: " . $status);
		
		if (!$isCompleted) {
			$buttonText = $isCurrent
				? OmniQuest::getInstance()->getQuestConfig()->get("button-info-pause")
				: OmniQuest::getInstance()->getQuestConfig()->get("button-info-start");
			$this->addToggle($buttonText, false);
		} else {
			$this->addLabel("\n§7This quest has been completed!");
		}
	}
}