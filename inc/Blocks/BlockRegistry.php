<?php
// inc/Blocks/BlockRegistry.php

namespace TAW\Blocks;

use TAW\Blocks\BaseBlock;

class BlockRegistry
{
    /** @var array<string, BaseBlock> */
    private static array $components = [];

    public static function register(BaseBlock $component): void
    {
        self::$components[$component->getId()] = $component;
    }

    public static function get(string $id): ?BaseBlock
    {
        return self::$components[$id] ?? null;
    }

    /**
     * Shortcut: get and render in one call
     */
    public static function render(string $id, ?int $postId = null): void
    {
        $component = self::get($id);
        if ($component) {
            $component->render($postId);
        }
    }
}
