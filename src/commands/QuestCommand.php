<?php

namespace AmitxD\OmniQuest\commands;

use pocketmine\ {
    player\Player,
    command\Command,
    command\CommandSender
};

use AmitxD\OmniQuest\ {
    OmniQuest,
    Manager\QuestManager,
    forms\CategoryForm,
    forms\QuestForm,
    forms\QuestInfoForm
};

class QuestCommand extends Command
{

    private $plugin;
    private $category;
    private $quest;

    public function __construct(OmniQuest $plugin, string $name) {
        parent::__construct($name, "open up the quest menu");
        $this->setAliases(['q', 'omniquest']);
        $this->plugin = $plugin;
        $this->setDescription("open up the quest menu");
        $this->setPermission("omniquest.command.use");
    }

    public function execute(CommandSender $sender, string $label, array $args): void {
        if (!$sender instanceof Player) return;

        $this->showCategoryForm($sender);
    }

    private function showCategoryForm(Player $player): void {
        $form = new CategoryForm(function (Player $player, $data = null): void {
            if (is_null($data)) return;
            $this->category[$player->getName()] = $data;
            $this->showQuestForm($player);
        });
        $player->sendForm($form);
    }

    private function showQuestForm(Player $player): void {
        $form = new QuestForm(function(Player $player, $data = null): void {
            if (is_null($data)) {
                $this->showCategoryForm($player); // Return to categories when exit pressed
                return;
            }
            $this->quest[$player->getName()] = QuestManager::getQuestNameById($data, $this->category[$player->getName()]);
            if (QuestManager::isCompleted($player->getName(), $this->quest[$player->getName()])) {
                $player->sendMessage($this->plugin->getQuestConfig()->get("quest-already-finished"));
            }
            $this->showQuestInfoForm($player, $data);
        }, $player, $this->category[$player->getName()]);
        $player->sendForm($form);
    }

    private function showQuestInfoForm(Player $player, int $questId): void {
        $form = new QuestInfoForm(function(Player $player, $data = null): void {
            if (is_null($data)) {
                $this->showQuestForm($player); // Return to quest list when exit pressed
                return;
            }
            // Check if the toggle button was enabled (last element in the form)
            if (end($data) === true) {
                QuestManager::updateQuest($player, $this->category[$player->getName()], $this->quest[$player->getName()]);
            }
        }, $player, $this->category[$player->getName()], $questId);
        $player->sendForm($form);
    }
}