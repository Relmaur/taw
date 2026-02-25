<?php
// inc/Components/ComponentRegistry.php

namespace TAW\Components;

use TAW\Components\BaseComponent;

class ComponentRegistry
{
    /** @var array<string, BaseComponent> */
    private static array $components = [];

    public static function register(BaseComponent $component): void
    {
        self::$components[$component->getId()] = $component;
    }

    public static function get(string $id): ?BaseComponent
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
