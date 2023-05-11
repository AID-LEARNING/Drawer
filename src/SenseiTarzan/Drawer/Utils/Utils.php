<?php

namespace SenseiTarzan\Drawer\Utils;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class Utils
{

    public static function counterItemInInventory(Inventory $inventory, Item $item){
        $count = 0;
        foreach ($inventory->all($item) as$item) {
            $count += $item->getCount();
        }
        return $count;
    }
}