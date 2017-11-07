<?php

use \Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) {
    die();
}

$arComponentDescription = [
    'NAME' => Loc::getMessage('MAXIMASTER:COUPANDA.COUPON.GENERATOR:COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('MAXIMASTER:COUPANDA.COUPON.GENERATOR:COMPONENT_DESCRIPTION'),
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'maximaster',
        'NAME' => Loc::getMessage('MAXIMASTER:COUPANDA.COUPON.GENERATOR:VENDOR_NAME'),
        'CHILD' => [
            'ID' => 'coupanda.coupon.generator',
            'NAME' => Loc::getMessage('MAXIMASTER:COUPANDA.COUPON.GENERATOR:COMPONENT_NAME')
        ]
    ],
];
