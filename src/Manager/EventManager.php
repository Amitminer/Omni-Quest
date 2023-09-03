<?php

namespace AmitxD\OmniQuest\Manager;;

use pocketmine\event\ {
    Listener,
    block\BlockPlaceEvent,
    block\BlockBreakEvent,
    player\PlayerLoginEvent,
    player\PlayerMoveEvent,
    player\PlayerDeathEvent,
    entity\EntityDamageByEntityEvent
};
use pocketmine\player\Player;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use AmitxD\OmniQuest\Utils\Utils;
use AmitxD\OmniQuest\OmniQuest;

class EventManager implements Listener
{

    private $plugin;

    public function __construct(OmniQuest $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerLogin(PlayerLoginEvent $ev): void {
        $player = $ev->getPlayer();

        QuestManager::registerUser($player->getName());
    }

    public function onBreakBlock(BlockBreakEvent $ev): void {
        $quests = $this->plugin->getQuests();
        $block = $ev->getBlock();

        if ($ev->isCancelled()) return;

        foreach ($quests as $name => $value) {
            $player = $ev->getPlayer();
            if ($value["type"] === "breakblock" && QuestManager::isCurrent($player->getName(), $name)) {
                $b = Utils::stringToBlock($value["block"]);
                if ($block->getTypeId() === $b->getTypeId()) QuestManager::incrementProgress($player, $name);
            }
        }
    }

    public function onPlaceBlock(BlockPlaceEvent $ev): void {
        $quests = $this->plugin->getQuests();

        if ($ev->isCancelled()) return;

        foreach ($quests as $name => $value) {
            $player = $ev->getPlayer();
            if ($value["type"] === "placeblock" && QuestManager::isCurrent($player->getName(), $name)) {
                $b = Utils::stringToBlock($value["block"]);
                foreach ($ev->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
                    if ($block->getTypeId() === $b->getTypeId()) QuestManager::incrementProgress($player, $name);
                }
            }
        }
    }

    public function onDeath(PlayerDeathEvent $ev): void {
        $player = $ev->getPlayer();
        $quests = $this->plugin->getQuests();

        $cause = $player->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                foreach ($quests as $name => $value) {
                    if ($value["type"] === "kills" && QuestManager::isCurrent($damager->getName(), $name)) {
                        QuestManager::incrementProgress($damager, $name);
                    }
                }
            }
        }
    }

    public function onMove(PlayerMoveEvent $ev): void {
        $quests = $this->plugin->getQuests();
        $player = $ev->getPlayer();
        $from = $ev->getFrom();
        $to = $ev->getTo();

        if ($ev->isCancelled()) return;

        if ($from->getX() !== $to->getX() && $from->getZ() !== $to->getZ()) {
            foreach ($quests as $name => $value) {
                if ($value["type"] === "move" && QuestManager::isCurrent($player->getName(), $name)) {
                    QuestManager::incrementProgress($player, $name);
                }
            }
        }
    }
}