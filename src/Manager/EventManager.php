<?php

namespace AmitxD\OmniQuest\Manager;

use pocketmine\event\{
    Listener,
    block\BlockPlaceEvent,
    block\BlockBreakEvent,
    player\PlayerLoginEvent,
    player\PlayerMoveEvent,
    player\PlayerDeathEvent,
    entity\EntityDamageByEntityEvent,
    player\PlayerCommandPreprocessEvent
};
use pocketmine\player\Player;
use AmitxD\OmniQuest\Utils\Utils;
use AmitxD\OmniQuest\OmniQuest;
use pocketmine\event\server\CommandEvent;

class EventManager implements Listener
{
    private OmniQuest $plugin;
    private array $quests = [];

    public function __construct(OmniQuest $plugin)
    {
        $this->plugin = $plugin;
        $this->quests = $plugin->getQuests();
    }

    public function onPlayerLogin(PlayerLoginEvent $ev): void
    {
        QuestManager::registerUser($ev->getPlayer()->getName());
    }

    public function onBreakBlock(BlockBreakEvent $ev): void
    {
        if ($ev->isCancelled()) return;

        $player = $ev->getPlayer();
        $blockTypeId = $ev->getBlock()->getTypeId();

        foreach ($this->quests as $name => $value) {
            if ($value["type"] === "breakblock") {
                $targetBlockId = Utils::stringToBlock($value["block"])->getTypeId();
                if ($blockTypeId === $targetBlockId && QuestManager::isCurrent($player->getName(), $name)) {
                    QuestManager::incrementProgress($player, $name);
                }
            }
        }
    }

    public function onPlaceBlock(BlockPlaceEvent $ev): void
    {
        if ($ev->isCancelled()) return;

        $player = $ev->getPlayer();

        foreach ($this->quests as $name => $value) {
            if ($value["type"] === "placeblock") {
                $targetBlockId = Utils::stringToBlock($value["block"])->getTypeId();
                if (!QuestManager::isCurrent($player->getName(), $name)) continue; 

                foreach ($ev->getTransaction()->getBlocks() as [,,, $block]) {
                    if ($block->getTypeId() === $targetBlockId) {
                        QuestManager::incrementProgress($player, $name);
                        break;
                    }
                }
            }
        }
    }

    public function onDeath(PlayerDeathEvent $ev): void
    {
        $player = $ev->getPlayer();
        $cause = $player->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                $damagerName = $damager->getName();

                foreach ($this->quests as $name => $value) {
                    if ($value["type"] === "kills" && QuestManager::isCurrent($damagerName, $name)) {
                        QuestManager::incrementProgress($damager, $name);
                    }
                }
            }
        }
    }

    public function onMove(PlayerMoveEvent $ev): void
    {
        if ($ev->isCancelled()) return;

        $player = $ev->getPlayer();
        $from = $ev->getFrom();
        $to = $ev->getTo();

        if ($from->distanceSquared($to) < 0.01) return;

        $playerName = $player->getName();

        foreach ($this->quests as $name => $value) {
            if ($value["type"] === "move" && QuestManager::isCurrent($playerName, $name)) {
                QuestManager::incrementProgress($player, $name);
            }
        }
    }

    public function onCommandInvoke(CommandEvent $event): void
    {
        $commandString = $event->getCommand();
        $player = $event->getSender();

        if (!$player instanceof Player) return; 

        $args = explode(" ", $commandString);
        $command = strtolower(array_shift($args));

        foreach ($this->quests as $name => $quest) {
            if (isset($quest["type"]) && $quest["type"] === "command" && isset($quest["command"])) {
                $questCommand = strtolower($quest["command"]);
                if ($command === $questCommand) {
                    if (QuestManager::isCurrent($player->getName(), $name)) {
                        QuestManager::setCompleted($player->getName(), $name);
                        QuestManager::resetQuest($player->getName());
                        $player->sendMessage(str_replace("{QUEST}", $name, $this->plugin->getQuestConfig()->get("finished-quest-message")));
                    }
                }
            }
        }
    }
}
