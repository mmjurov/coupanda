<?php

namespace Maximaster\Coupanda\Compability;

use Bitrix\Main\Error;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;

class CompabilityChecker
{
    public function check()
    {
        $result = new Result();
        if (!$this->isSaleInstalled()) {
            $result->addError(new Error('Модуль "Интернет-магазин" не установлен. Необходимо установить модуль'));
        } else {
            if (!$this->isSaleVersionCompatible()) {
                $result->addError(new Error('Версия модуля "Интернет-магазин" является слишком старой'));
            }
        }

        return $result;
    }

    public function isSaleInstalled()
    {
        return ModuleManager::isModuleInstalled('sale');
    }

    public function isSaleVersionCompatible()
    {
        $saleVersion = ModuleManager::getVersion('sale');
        if (!$saleVersion) {
            return false;
        }

        return \CheckVersion($saleVersion, '14.11.0');
    }
}
