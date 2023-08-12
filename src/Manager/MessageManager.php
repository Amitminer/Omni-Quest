<?php

declare(strict_types = 1);

namespace AmitxD\OmniQuest\Manager;

use pocketmine\utils\TextFormat as TF;

class MessageManager {
    
    public function __construct() {
        // NOOP (No operation)
    }
    
    public const NOCONSOLE = TF::RED . "Please Use this command in game!";
    public const CATEGORY_USAGE = TF::RED . "Usage: /questcategory <list|add|remove>";
}