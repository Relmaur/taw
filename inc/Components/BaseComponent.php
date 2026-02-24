<?php

declare(strict_types=1);

namespace TAW\Components;

abstract class BaseComponent
{

    protected string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    abstract protected static function render();

    abstract protected static function enqueueAssets();
}