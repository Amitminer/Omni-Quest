<?php

declare(strict_types = 1);

namespace AmitxD\OmniQuest\Utils;

use pocketmine\block\Block;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
class Utils {

    /**
    * Converts a string representation of an Block to an Block instance.
    * @param string $input The string representation of the Block.
    * @return Block|null The Item instance if the conversion was successful, or null if failed.
    */
    public static function stringToBlock(string $input): ?Block {
        $string = strtolower(str_replace([' ', 'minecraft:'], ['_', ''], trim($input)));
        try {
            $item = StringToItemParser::getInstance()->parse($string) ?? LegacyStringToItemParser::getInstance()->parse($string);
        } catch (LegacyStringToItemParserException $e) {
            return null;
        }
        return $item->getBlock();
    }
}