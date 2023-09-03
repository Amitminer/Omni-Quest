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

        $form = new CategoryForm(function (Player $player, int $data = null): void {
            if (is_null($data)) return;
            $this->category[$player->getName()] = $data;

            $form = new QuestForm(function(Player $player, int $data = null): void {
                if (is_null($data)) return;
                $this->quest[$player->getName()] = QuestManager::getQuestNameById($data, $this->category[$player->getName()]);
                if (QuestManager::isCompleted($player->getName(), $this->quest[$player->getName()])) $player->sendMessage($this->plugin->getQuestConfig()->get("quest-already-finished"));

                $form = new QuestInfoForm(function(Player $player, bool $data = null): void {
                    if (is_null($data)) return;
                    if ($data) QuestManager::updateQuest($player, $this->category[$player->getName()], $this->quest[$player->getName()]);
                },
                    $player,
                    $this->category[$player->getName()],
                    $data);
                $player->sendForm($form);
            }, $player, $data);
            $player->sendForm($form);
        });
        $sender->sendForm($form);
    }
}