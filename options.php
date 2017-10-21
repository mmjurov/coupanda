<?php

$moduleId = 'maximaster.coupanda';
/** @global \CMain $APPLICATION */

$SALE_RIGHT = $APPLICATION->GetGroupRight($moduleId);

\IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/options.php');
\IncludeModuleLangFile(__FILE__);
\CModule::IncludeModule($moduleId);