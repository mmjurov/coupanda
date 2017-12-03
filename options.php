<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

// Обязательно необходимо определить переменную с именем module_id, иначе система прав доступа будет работать
// с логикой по дефолту, игнорируя созданные права доступа в классе модуля
$moduleId = $module_id = 'maximaster.coupanda';

/** @global \CMain $APPLICATION */
$moduleAccessLevel = $APPLICATION->GetGroupRight($moduleId);

\IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
\IncludeModuleLangFile(__FILE__);

if ($moduleAccessLevel < 'W') {
    \ShowError('Доступ к модулю запрещен');
    return;
}

Loader::IncludeModule($moduleId);
Loc::loadMessages(__FILE__);

$tabs = [
    [
        'DIV' => 'maximaster_coupanda_access',
        'TAB' => 'Доступ',
        'ICON' => '',
        'TITLE' => 'Настройки доступа к модулю'
    ],
];
$tabControl = new \CAdminTabControl('maximaster_coupanda_options', $tabs, true, true);
$tabControl->Begin();
?>

<form method="POST"
      action="<?=$APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>&mid=<?=$moduleId?>"
      name="maximaster_coupanda_settings">
    <?$tabControl->BeginNextTab();?>
    <?require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';?>
    <?$tabControl->Buttons();?>
    <input type="submit" value="Сохранить">
    <input type="hidden" name="Update" value="Y">
    <?$tabControl->End();?>
</form>
