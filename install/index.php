<?php

\IncludeModuleLangFile(__FILE__);

if (\class_exists('maximaster.coupanda')) {
    return;
}

class maximaster_coupanda extends \CModule
{
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_ID;
    public $MODULE_SORT;
    public $SHOW_SUPER_ADMIN_GROUP_RIGHTS;
    public $MODULE_GROUP_RIGHTS;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $this->initModuleId();
        $this->initModuleVersionDefinition();
        $this->initModuleName();
        $this->initModuleDescription();
        $this->initModulePartnerInfo();
        $this->initModuleGroupRights();
    }

    protected function initModuleId()
    {
        $this->MODULE_ID = 'maximaster.coupanda';
    }

    protected function initModuleName()
    {
        $this->MODULE_NAME = \Bitrix\Main\Localization\Loc::getMessage('MAXIMASTER.COUPANDA:MODULE_NAME');
    }

    protected function initModuleDescription()
    {
        $this->MODULE_DESCRIPTION = \Bitrix\Main\Localization\Loc::getMessage('MAXIMASTER.COUPANDA:MODULE_DESCRIPTION');
    }

    protected function initModuleVersionDefinition()
    {
        $versionDefinition = $this->getModuleVersionDefinition();
        $this->MODULE_VERSION = $versionDefinition['VERSION'];
        $this->MODULE_VERSION_DATE = $versionDefinition['VERSION_DATE'];
    }

    protected function getDefaultVersionDefinition()
    {
        return [
            'VERSION' => '1.0.0',
            'VERSION_DATE' => '1970-01-01 00:00:00'
        ];
    }

    protected function getModuleVersionDefinition()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $defaultVersionDefinition = $this->getDefaultVersionDefinition();
        if (!\is_array($arModuleVersion) || empty($arModuleVersion)) {
            return $defaultVersionDefinition;
        }

        $version = isset($arModuleVersion['VERSION']) ? $arModuleVersion['VERSION']
            : $defaultVersionDefinition['VERSION'];
        $versionDate = isset($arModuleVersion['VERSION_DATE']) ? $arModuleVersion['VERSION_DATE']
            : $defaultVersionDefinition['VERSION_DATE'];

        return [
            'VERSION' => $version,
            'VERSION_DATE' => $versionDate
        ];
    }

    protected function initModulePartnerInfo()
    {
        $this->PARTNER_NAME = \GetMessage('MAXIMASTER.COUPANDA:MODULE_PARTNER_NAME');
        $this->PARTNER_URI = 'http://www.maximaster.ru';
    }

    protected function initModuleGroupRights()
    {
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }

    public function DoUninstall()
    {
        if (!$this->UnInstallDB()) {
            return false;
        }

        $this->UnInstallEvents();
        $this->UnInstallFiles();

        return true;
    }

    public function GetModuleRightList()
    {
        $rightsReferenceIds = ['D', 'W'];
        $references = [];

        foreach ($rightsReferenceIds as $referenceId) {
            $references[] = "[{$referenceId}] " . \GetMessage('MAXIMASTER.COUPANDA:MODULE_RIGHTS_REFERENCE_' . $referenceId);
        }

        return [
            'reference_id' => $rightsReferenceIds,
            'reference' => $references,
        ];
    }

    /**
     * Метод должен либо вернуть массив с описанием ролей, либо ничего не вернуть
     * @return array
     */
    public function GetModuleTasks()
    {
        return [];
    }

    public function DoInstall()
    {
        if (!$this->InstallDB()) {
            return false;
        }

        $this->InstallEvents();
        $this->InstallFiles();

        return true;
    }

    public function InstallDB()
    {
        $connection = \Bitrix\Main\Application::getConnection();
        try {
            $connection->startTransaction();
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            \Bitrix\Main\Loader::includeModule($this->MODULE_ID);
            $this->createProcessTable($connection);
            $this->addPIDColumn($connection);
            $connection->commitTransaction();
        } catch (\Exception $e) {
            $connection->rollbackTransaction();
            global $APPLICATION;
            $APPLICATION->ResetException();
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }

        return true;
    }

    protected function createProcessTable(\Bitrix\Main\DB\Connection $connection)
    {
        $tableName = \Maximaster\Coupanda\Orm\ProcessTable::getTableName();
        if (!$connection->isTableExists($tableName)) {
            $connection->createTable(\Maximaster\Coupanda\Orm\ProcessTable::getTableName(),
                \Maximaster\Coupanda\Orm\ProcessTable::getMap(), ['ID'], ['ID']);
        }
    }

    protected function dropProcessTable(\Bitrix\Main\DB\Connection $connection)
    {
        $tableName = \Maximaster\Coupanda\Orm\ProcessTable::getTableName();
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    protected function addPIDColumn(\Bitrix\Main\DB\Connection $connection)
    {
        $connection->queryExecute('ALTER TABLE `b_sale_discount_coupon` ADD `MAXIMASTER_COUPANDA_PID` INT NULL DEFAULT NULL');
    }

    protected function dropPIDColumn(\Bitrix\Main\DB\Connection $connection)
    {
        $connection->queryExecute('ALTER TABLE `b_sale_discount_coupon` DROP `MAXIMASTER_COUPANDA_PID`');
    }

    public function InstallFiles()
    {
        return true;
    }

    public function InstallEvents()
    {
        return true;
    }

    public function UninstallDB()
    {
        $connection = \Bitrix\Main\Application::getConnection();

        try {
            \Bitrix\Main\Loader::includeModule($this->MODULE_ID);
            $connection->startTransaction();
            $this->dropProcessTable($connection);
            $this->dropPIDColumn($connection);
            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
            $connection->commitTransaction();
        } catch (\Exception $e) {
            $connection->rollbackTransaction();
            global $APPLICATION;
            $APPLICATION->ResetException();
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }

        return true;
    }

    public function UninstallFiles()
    {
        return true;
    }

    public function UninstallEvents()
    {
        return true;
    }
}
