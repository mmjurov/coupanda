<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

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

$request = Context::getCurrent()->getRequest();
$logLevelOption = Option::get($moduleId, 'log_level', 0);

$logLevels = [
    'none', 'error', 'all'
];

if ($request->isPost() && check_bitrix_sessid()) {
    //Тут будет сохранение прав
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
    $logLevel = $request->getPost('log_level');
    if ($logLevel !== $logLevelOption) {
        Option::set($moduleId, 'log_level', $logLevel);
        $logLevelOption = $logLevel;
    }

    LocalRedirect($request->getRequestUri());
    die;
}

Loader::IncludeModule($moduleId);
Loc::loadMessages(__FILE__);

$tabs = [
    [
        'DIV' => 'maximaster_coupanda_options',
        'TAB' => 'Настройки модуля',
        'ICON' => '',
        'TITLE' => 'Настройки модуля'
    ],
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
    <?=bitrix_sessid_post()?>
    <?$tabControl->BeginNextTab();?>
    <tr>
        <td width="50%"><label for="logging">Логирование</label>:</td>
        <td width="50%">
            <select name="log_level" id="logging">
                <?foreach ($logLevels as $level):?>
                    <option value="<?=$level?>" <?=$level === $logLevelOption ? 'selected' : ''?>>
                        <?=Loc::getMessage('COUPANDA:LOG_LEVEL:' . ToUpper($level))?>
                    </option>
                <?endforeach;?>
            </select>
        </td>
    </tr>
    <?$tabControl->BeginNextTab();?>
    <?require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';?>
    <?$tabControl->Buttons();?>
    <input type="submit" value="Сохранить">
    <input type="hidden" name="Update" value="Y">
    <?$tabControl->End();?>
</form>
