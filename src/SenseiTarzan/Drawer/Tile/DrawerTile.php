<?php

namespace SenseiTarzan\Drawer\Tile;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\tile\Spawnable;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\world\World;

class DrawerTile extends Spawnable
{
    private Item $stock;

    public int $maxStock = 64;

    const TAG_STOCK = "stock";
    const TAG_MAX_STOCK = "max_stock";

    public function __construct(World $world, Vector3 $pos)
    {
        $this->setStock(self::AirItem());
        parent::__construct($world, $pos);
    }

    /**
     * @return Item
     */
    public function getStock(): Item
    {
        return $this->stock;
    }

    public function hasStock(): bool
    {
        return !$this->stock->isNull() || $this->stock->getTypeId() !== -BlockTypeIds::AIR;
    }

    public function setStock(Item $item): void
    {
        $this->stock = $item;
    }

    public function isEqualsStock(Item $item): bool
    {
        return !$this->hasStock() || $this->stock->equals($item);
    }

    public function addStock(int &$count): bool
    {
        if (!$this->hasStock()) return false;
        if ($this->isMaxStock()) return false;
        $total = $this->stock->getCount() + $count;
        if ( $total >= $this->getMaxStock()){
            $count = $this->getMaxStock() - $this->getStock()->getCount();
            $total = $this->getMaxStock();
        }
        if ($total === 0) return false;
        $this->stock->setCount($total);
        return true;
    }

    public function removeStock(int &$count): bool|Item
    {
        if (!$this->hasStock()) return false;
        if ($count > $this->stock->getCount()) {
            $count = $this->stock->getCount();
        }
        $item = clone $this->stock;
        $item->setCount($count);
        $this->stock->setCount($this->stock->getCount() - $count);
        if ($this->stock->getCount() <= 0) $this->setStock(self::AirItem());
        return $item;
    }

    public function isMaxStock(): bool
    {
        return $this->stock->getCount() >= $this->getMaxStock();
    }

    public static function AirItem(): Item
    {
        return VanillaBlocks::AIR()->asItem()->setCount(-1);
    }

    /**
     * @return int
     */
    public function getMaxStock(): int
    {
        return $this->maxStock;
    }


    /**
     * @inheritDoc
     */
    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {

    }

    /**
     * @inheritDoc
     */
    public function readSaveData(CompoundTag $nbt): void
    {
        $this->setStock(self::fromNbt($nbt->getCompoundTag(self::TAG_STOCK)));
        $this->maxStock = $nbt->getInt(self::TAG_MAX_STOCK, $this->getMaxStock());
    }

    /**
     * @inheritDoc
     */
    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setTag("stock", self::toNbt($this->getStock()));
        $nbt->setInt("max_stock", $this->getMaxStock());
    }

    public static function toNbt(Item $item): CompoundTag
    {
        if ($item->isNull() || $item->getTypeId() === -BlockTypeIds::AIR) {
            return CompoundTag::create();
        }
        $typeData = GlobalItemDataHandlers::getSerializer()->serializeType($item);
        return CompoundTag::create()->setInt(SavedItemStackData::TAG_COUNT, $item->getCount())->merge($typeData->toNbt());
    }

    public static function fromNbt(?CompoundTag $nbt): Item
    {
        if ($nbt === null || $nbt->count()  === 0) {
            return self::AirItem();
        }
        try {


            $methodUpgradeItemTypeNbt = new \ReflectionMethod($instance = GlobalItemDataHandlers::getUpgrader(), "upgradeItemTypeNbt");
            $methodUpgradeItemTypeNbt->setAccessible(true);
            /**
             * @var SavedItemData $itemSavedData
             */
            $itemSavedData = $methodUpgradeItemTypeNbt->invoke($instance, $nbt);
            unset($methodUpgradeItemTypeNbt);
            if ($itemSavedData === null) {
                return self::AirItem();
            }
            return GlobalItemDataHandlers::getDeserializer()->deserializeType($itemSavedData)->setCount($nbt->getInt(SavedItemStackData::TAG_COUNT, 1));
        } catch (\Exception) {
            return self::AirItem();
        }
    }
}