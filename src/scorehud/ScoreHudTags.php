<?php

declare(strict_types=1);

namespace AmitxD\OmniQuest\scorehud;

interface ScoreHudTags
{
    /** @var string */ 
    public const PREFIX = "omniquest.";
    
    /** @var string */ 
    public const CURRENT_QUEST_NAME = self::PREFIX . "current";
    /** @var string */
    public const QUEST_PROGRESS = self::PREFIX . "progress";
    /** @var string */
    public const QUEST_TOTAL = self::PREFIX . "total";
    /** @var string */
    public const QUEST_PERCENT = self::PREFIX . "percent";
    /** @var string */
    public const QUEST_TYPE = self::PREFIX . "type";
    
    public const NOT_AVAILABLE = "N/A";
}

