<?php

namespace SenseiTarzan\Drawer\Component;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\BlockTypeIds;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Drawer\Block\DrawerBlock;
use SenseiTarzan\Drawer\Tile\DrawerTile;
use SenseiTarzan\Drawer\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;

/**
 * @internal
 */
final class DrawerManager
{
    use SingletonTrait;

    public function UIIndex(Player $player, DrawerTile $tile): void
    {
        $ui = new SimpleForm(function (Player $player, ?int $data) use ($tile): void {

            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->UIAddStock($player, $tile);
                    break;
                case 1:
                    if (!$tile->hasStock()) {
                        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_empty_drawer()));
                        return;
                    }
                    $this->UIRemoveStock($player, $tile);
                    break;
            }
        });

        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_title($tile->getBlock()->getName())));
        $ui->setContent(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_content(($tile->hasStock() ? $tile->getStock() : null), $tile->getMaxStock())));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_add_stock_buttons()));
        $ui->addButton(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_remove_stock_buttons()));
        $player->sendForm($ui);
    }

    private function UIAddStock(Player $player, DrawerTile $tile): void
    {
        $ui = new CustomForm(function (Player $player, ?array $data) use ($tile): void {
            if ($data === null) return;
            $count = intval($data["count"]);
            if ($count <= 0) {
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_number_zero()));
                return;
            }
            $checkTiles = $tile->getPosition()->getWorld()->getTile($tile->getPosition());
            if ($checkTiles instanceof $tile) {

                if (!$tile->hasStock()) {
                    $item = clone $player->getInventory()->getItemInHand();

                    if ($item->getCount() < $count) {
                        $count = $item->getCount();
                    }
                    if ($item->isNull() || $item->getTypeId() === -BlockTypeIds::AIR  || $item->getBlock() instanceof DrawerBlock) {
                        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_air_item()));
                        return;
                    }
                    $item->setCount($count);
                    $tile->setStock($item);
                    $player->getInventory()->removeItem($item);
                    return;
                }
                $item = clone $tile->getStock();
                $item->setCount($count);
                if (!$player->getInventory()->contains($item)) {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_contains_item($item)));
                    return;
                }
                if (!$tile->addStock($count)) {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_full_drawer()));
                    return;
                }
                $player->getInventory()->removeItem($item->setCount($count));
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_add_item($item, $count)));

            }

        });
        $ui->setTitle($tile->getBlock()->getName());
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_count_input()), $count = $tile->getMaxStock() - $tile->getStock()->getCount(), $count, "count");
        $player->sendForm($ui);


    }

    private function UIRemoveStock(Player $player, DrawerTile $tile): void
    {
        $ui = new CustomForm(function (Player $player, ?array $data) use ($tile): void {
            if ($data === null) return;
            $count = intval($data["count"]);
            if ($count <= 0) {
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_number_zero()));
                return;
            }
            $checkTiles = $tile->getPosition()->getWorld()->getTile($tile->getPosition());
            if ($checkTiles instanceof $tile) {
                if ($tile->hasStock()) {
                    if ($item = $tile->removeStock($count)) {
                        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_remove_item($item, $count)));
                        $player->getInventory()->addItem($item);
                    }
                } else {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_empty_drawer()));
                }
            }

        });
        $ui->setTitle(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_title($tile->getBlock()->getName())));
        $ui->addInput(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_count_input()), $tile->getStock()->getCount(), $tile->getStock()->getCount(), "count");
        $player->sendForm($ui);
    }

}