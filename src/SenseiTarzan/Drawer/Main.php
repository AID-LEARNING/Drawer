<?php

namespace SenseiTarzan\Drawer;

use pmmp\thread\ThreadSafeArray;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\tile\TileFactory;
use pocketmine\data\bedrock\block\convert\BlockStateWriter as Writer;
use pocketmine\inventory\CreativeInventory;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use ReflectionException;
use SenseiTarzan\Drawer\Block\DrawerBlock;
use SenseiTarzan\Drawer\Listener\PlayerListener;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\HackBlockAndItemRegistry\HackRegisterBlock;
use SenseiTarzan\LanguageSystem\Component\LanguageManager;

class Main extends PluginBase
{

    public function onLoad(): void
    {
        new LanguageManager($this);
    }

    /**
     * @throws ReflectionException
     */
    protected function onEnable(): void
    {
        LanguageManager::getInstance()->loadCommands("drawer");
        EventLoader::loadEventWithClass($this, PlayerListener::class);
        $blocks = new ThreadSafeArray();
        $tiles = new ThreadSafeArray();
        $configAll = $this->getConfig()->getAll();
        foreach ($configAll as $blockName => $blockData) {
            if (!isset($blockData["id-legacy"])) continue;
            $blockName = str_replace(" ", "_", strtolower($blockName));
            $maxStockPlace = $blockData["max-stack-on-place"];
            $TileClassName = implode("", array_map(fn($word) => ucfirst($word), explode("_", $blockName))) . "Tile";
            $tile = eval($instanceTileClass = 'namespace SenseiTarzan\Drawer\Tile;class ' . $TileClassName . ' extends DrawerTile { public int $maxStock = ' . $maxStockPlace . ';} return  ' . $TileClassName . '::class;');

            $tiles[$blockData["id-legacy"]] = $instanceTileClass;

            $blocks[$blockData["id-legacy"]] = igbinary_serialize([
                $block = new DrawerBlock(
                    new BlockIdentifier(
                        $blockData["id"] ?? BlockTypeIds::newId(),
                        $tile
                    ),
                    $blockData["name"],
                    new BlockTypeInfo(
                        new BlockBreakInfo(
                            $blockData["hardness"],
                            match (strtoupper(($blockData["toolType"] ?? "pickaxe"))) {
                                "PICKAXE" => BlockToolType::PICKAXE,
                                "SHOVEL" => BlockToolType::SHOVEL,
                                "AXE" => BlockToolType::AXE,
                                "SHEARS" => BlockToolType::SHEARS,
                                "SWORD" => BlockToolType::SWORD,
                                default => BlockToolType::NONE
                            },
                            $blockData["toolHarvestLevel"],
                            $blockData["blastResistance"])
                    )
                ),
                $blockData['serializer'] ?? null,
                $blockData['deserializer'] ?? null
            ]);
            $realNameTile = str_replace("Tile", "", ($realNameTile = explode("\\", $tile))[array_key_last($realNameTile)]);
            TileFactory::getInstance()->register($tile, [strtolower("senseitarzan:" . $realNameTile), strtolower($realNameTile)]);
            $serializer = isset($blockData['serializer']) ? (eval($blockData['serializer']))($blockData["id-legacy"]) : (fn() => Writer::create($blockData["id-legacy"]));
            $deserializer = isset($blockData['deserializer']) ? (eval($blockData['deserializer']))(clone $block) : (fn() => clone $block);
            HackRegisterBlock::registerBlockAndSerializerAndDeserializer($block, $blockData["id-legacy"], $serializer, $deserializer);
            CreativeInventory::getInstance()->remove($item = $block->asItem());
            CreativeInventory::getInstance()->add($item);
        }
        $asyncPool = $this->getServer()->getAsyncPool();
        $asyncPool->addWorkerStartHook(function (int $worker) use ($asyncPool, $blocks, $tiles): void {
            $asyncPool->submitTaskToWorker(new class($blocks, $tiles) extends AsyncTask {
                public function __construct(private ThreadSafeArray $blocks, private ThreadSafeArray $tiles)
                {
                }

                public function onRun(): void
                {
                    foreach ($this->tiles as $blockName => $tile) {
                        $tile = eval($tile);
                        $realNameTile = str_replace("Tile", "", ($realNameTile = explode("\\", $tile))[array_key_last($realNameTile)]);
                        TileFactory::getInstance()->register($tile, [strtolower("senseitarzan:" . $realNameTile), strtolower($realNameTile)]);
                        /**
                         * @var DrawerBlock $block
                         * @var string|null $serializer
                         * @var string|null $deserializer
                         */
                        [$block, $serializer, $deserializer] = igbinary_unserialize($this->blocks[$blockName]);
                        HackRegisterBlock::registerBlockAndSerializerAndDeserializer($block, $blockName, $serializer ? (eval($serializer))($blockName) : (fn() => Writer::create($blockName)), $deserializer ? (eval($deserializer))(clone $block) : (fn() => clone $block));
                    }
                }
            }, $worker);
        });
    }
}