<?php
// inc/Components/Button/Button.php

namespace TAW\Components\Button;

use TAW\Components\UIComponent;

class Button extends UIComponent
{
    protected string $id = 'button';

    protected function defaults(): array
    {
        return [
            'text'    => '',
            'url'     => '#',
            'variant' => 'primary',  // 'primary', 'secondary', 'ghost'
            'target'  => '_self',
        ];
    }
}