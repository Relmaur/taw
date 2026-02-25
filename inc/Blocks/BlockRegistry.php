<?php
// inc/Blocks/BlockRegistry.php

namespace TAW\Blocks;

use TAW\Blocks\MetaBlock;

class BlockRegistry
{
    /** @var array<string, MetaBlock> */
    private static array $components = [];

    public static function register(MetaBlock $metablock): void
    {
        self::$components[$metablock->getId()] = $metablock;
    }

    public static function get(string $id): ?MetaBlock
    {
        return self::$components[$id] ?? null;
    }

    /**
     * Shortcut: get and render in one call
     */
    public static function render(string $id, ?int $postId = null): void
    {
        $metablock = self::get($id);
        if ($metablock) {
            $metablock->render($postId);
        }
    }
}
