<?php

namespace SenseiTarzan\Drawer\Listener;

use pocketmine\block\BlockTypeIds;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerInteractEvent;
use SenseiTarzan\Drawer\Block\DrawerBlock;
use SenseiTarzan\Drawer\Component\DrawerManager;
use SenseiTarzan\Drawer\Tile\DrawerTile;
use SenseiTarzan\Drawer\Utils\CustomKnownTranslationFactory;
use SenseiTarzan\Drawer\Utils\Utils;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;

class PlayerListener
{

    #[EventAttribute(EventPriority::HIGHEST)]
    public function onInteract(PlayerInteractEvent $event): void
    {
        if ($event->isCancelled() || $event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }
        $block = $event->getBlock();
        if ($block instanceof DrawerBlock) {
            $player = $event->getPlayer();

            /** @var DrawerTile|null $tile */
            $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
            if ($tile === null) {
                return;
            }
            $event->cancel();
            if ($player->isSneaking()) {
                if (!$tile->hasStock()) {
                    $item = clone $player->getInventory()->getItemInHand();
                    if ($item->isNull() || $item->getTypeId() === -BlockTypeIds::AIR || $item->getBlock() instanceof DrawerBlock) {
                        return;
                    }
                    $count = $item->getCount();
                    if ($count > $tile->getMaxStock()) {
                        $count = $tile->getMaxStock();
                    }
                    $tile->setStock($item->setCount($count));
                    $player->getInventory()->removeItem($item);
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_add_item($item, $count)));
                    return;
                }
                $item = clone $tile->getStock();
                if (!$item->equals($player->getInventory()->getItemInHand())) {
                    $count = 64;
                    if ($item = $tile->removeStock($count)) {
                        $player->getInventory()->addItem($item);
                        $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_remove_item($item, $count)));
                        return;
                    }
                    return;
                }

                $count = Utils::counterItemInInventory($player->getInventory(), $item);
                if ($count === 0) {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_no_contains_item($item)));
                    return;
                }
                if (!$tile->addStock($count)) {
                    $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::error_full_drawer()));
                    return;
                }
                $player->getInventory()->removeItem($item->setCount($count));
                $player->sendMessage(LanguageManager::getInstance()->getTranslateWithTranslatable($player, CustomKnownTranslationFactory::drawer_add_item($item, $count)));
                return;
            }
            DrawerManager::getInstance()->UIIndex($player, $tile);
        }

    }

}