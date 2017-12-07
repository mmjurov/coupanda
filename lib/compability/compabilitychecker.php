<?php

namespace Maximaster\Coupanda\Compability;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;

class CompabilityChecker
{
    public function check()
    {
        $result = new Result();
        if (!$this->isSaleInstalled()) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:SALE_IS_NOT_INSTALLED')));
        } else {
            if (!$this->isSaleVersionCompatible()) {
                $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:SALE_IS_VERY_OLD')));
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
