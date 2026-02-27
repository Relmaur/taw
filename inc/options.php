<?php

use TAW\Core\OptionsPage;

new OptionsPage([
    'id'         => 'taw_settings',
    'title'      => 'TAW Settings',
    'menu_title' => 'TAW Settings',
    'icon'       => 'dashicons-screenoptions',
    'position'   => 2,
    'fields'     => [
        ['id' => 'company_name',  'label' => 'Company Name',  'type' => 'text'],
        ['id' => 'company_phone', 'label' => 'Phone Number',  'type' => 'text'],
        ['id' => 'company_email', 'label' => 'Email Address',  'type' => 'text'],
        ['id' => 'footer_text',   'label' => 'Footer Copyright', 'type' => 'textarea'],
        ['id' => 'social_facebook',  'label' => 'Facebook URL',  'type' => 'url'],
        ['id' => 'social_instagram', 'label' => 'Instagram URL', 'type' => 'url'],
    ],
    'tabs' => [
        ['id' => 'general', 'label' => 'General',  'fields' => ['company_name', 'company_phone', 'company_email']],
        ['id' => 'footer',  'label' => 'Footer',   'fields' => ['footer_text']],
        ['id' => 'social',  'label' => 'Social',    'fields' => ['social_facebook', 'social_instagram']],
    ],
]);
