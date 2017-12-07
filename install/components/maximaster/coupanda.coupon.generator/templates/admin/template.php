<?php

namespace Maximaster\Coupanda\Generator\AdminTemplate;

use \Bitrix\Main\Localization\Loc;

function getHint($id, $hint)
{
    $id = 'hint_' . $id;
    $hint = \CUtil::JSEscape($hint);
    $html = <<<HTML
        <span id="{$id}"></span>
        <script>BX.hint_replace(BX('{$id}'), '{$hint}');</script>
HTML;
    return trim($html);
}
?>
<style>
    .popup-window--coupanda-padded {
        padding: 20px;
        max-width: 80%;
    }

    .popup-window__preview-coupon {
        margin: 5px 10px;
        display: inline-block;
    }
</style>
<?=\BeginNote();?>
<?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.NOTE');?>
<?=\EndNote();?>
<?

$tabs = [
    [
        'DIV' => 'configuration',
        'TAB' => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.SETTINGS'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.SETTINGS'),
    ],
    [
        'DIV' => 'progress',
        'TAB' => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.PROGRESS'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.PROGRESS'),
    ],
    [
        'DIV' => 'report',
        'TAB' => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.REPORT'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.REPORT'),
    ],
];

$tabControl = new \CAdminTabControl('coupanda_generator', $tabs, false, true);
$tabControl->Begin();
$tabControl->BeginNextTab();
include __DIR__ . '/configuration.php';
$tabControl->BeginNextTab();
echo '<div id="js-progress-block"></div>';
$tabControl->BeginNextTab();
echo '<div id="js-report-block"></div>';
$tabControl->End();
