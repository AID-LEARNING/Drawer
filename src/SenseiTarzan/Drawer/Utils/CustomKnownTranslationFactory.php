<?php

namespace SenseiTarzan\Drawer\Utils;

use pocketmine\item\Item;
use pocketmine\lang\Translatable;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class CustomKnownTranslationFactory
{

    public static function drawer_title(string $title): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_TITLE, ["title" => $title]);
    }

    public static function drawer_content(?Item $item, int $max): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_CONTENT, [
            "item" => $item?->getName() ?? "",
            "id" => ($item === null ? "" : GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName()),
            "count" => $item?->getCount() ?? 0,
            "max" => $max]);
    }

    public static function drawer_add_item(Item $item, int $count): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_ADD_ITEM, [
            "item" => $item->getName(),
            "id" => GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName(),
            "count" => $count]);
    }

    public static function drawer_remove_item(Item $item, int $count): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_REMOVE_ITEM, [
            "item" => $item->getName(),
            "id" => GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName(),
            "count" => $count]);
    }

    public static function error_full_drawer(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_FULL_DRAWER);
    }

    public static function error_empty_drawer(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_EMPTY_DRAWER);
    }

    public static function error_air_item(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_AIR_ITEM);
    }

    public static function error_no_equals_item(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NO_EQUALS_ITEM);
    }

    public static function error_no_contains_item(Item $item): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NO_CONTAINS_ITEM, [
            "item" => $item->getName(),
            "id" => GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName()]);
    }

    public static function drawer_add_stock_buttons(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_ADD_STOCK_BUTTONS);
    }

    public static function drawer_remove_stock_buttons(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_REMOVE_STOCK_BUTTONS);
    }

    public static function drawer_count_input(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::DRAWER_COUNT_INPUT);
    }

    public static function error_number_zero(): Translatable
    {
        return new Translatable(CustomKnownTranslationKeys::ERROR_NUMBER_ZERO);
    }

    public static function drawer_remove_stock_success(string $getVanillaName, int $count)
    {
    }
}