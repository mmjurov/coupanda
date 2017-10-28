<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use \Maximaster\Coupanda\DatabaseInstaller;
use \Maximaster\Coupanda\FileInstaller;

if (\class_exists('maximaster_coupanda')) {
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
    protected $moduleDir;

    public function __construct()
    {
        Loc::loadMessages(__FILE__);
        $this->initModuleId();
        $this->initModuleVersionDefinition();
        $this->initModuleName();
        $this->initModuleDescription();
        $this->initModulePartnerInfo();
        $this->initModuleGroupRights();
        require_once __DIR__ . '/DatabaseInstaller.php';
        require_once __DIR__ . '/FileInstaller.php';
    }

    protected function initModuleId()
    {
        $this->MODULE_ID = 'maximaster.coupanda';
    }

    protected function initModuleName()
    {
        $this->MODULE_NAME = Loc::getMessage('MAXIMASTER.COUPANDA:MODULE_NAME');
    }

    protected function initModuleDescription()
    {
        $this->MODULE_DESCRIPTION = Loc::getMessage('MAXIMASTER.COUPANDA:MODULE_DESCRIPTION');
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
        $this->PARTNER_NAME = Loc::getMessage('MAXIMASTER.COUPANDA:MODULE_PARTNER_NAME');
        $this->PARTNER_URI = 'http://www.maximaster.ru';
    }

    protected function initModuleGroupRights()
    {
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }

    public function DoUninstall()
    {
        // Сначала удаляем БД
        if (!$this->UnInstallDB()) {
            return false;
        }

        // Если БД удалилась, то молча сносим все остальное
        $this->UnInstallEvents();
        $this->UnInstallFiles();

        return true;
    }

    public function GetModuleRightList()
    {
        $rightsReferenceIds = ['D', 'W'];
        $references = [];

        foreach ($rightsReferenceIds as $referenceId) {
            $references[] = "[{$referenceId}] " . Loc::getMessage('MAXIMASTER.COUPANDA:MODULE_RIGHTS_REFERENCE_' . $referenceId);
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

        if (!$this->InstallFiles()) {
            $this->UninstallDB();
            return false;
        }

        if (!$this->InstallEvents()) {
            $this->UninstallDB();
            $this->UninstallFiles();
            return false;
        }

        return true;
    }

    public function InstallDB()
    {
        $connection = Application::getConnection();
        $installer = new DatabaseInstaller($this->MODULE_ID, $connection);
        try {
            return $installer->install();
        } catch (\Exception $e) {
            global $APPLICATION;
            $APPLICATION->ResetException();
            $APPLICATION->ThrowException(Loc::getMessage('MAXIMASTER.COUPANDA:DB_INSTALLATION_ERROR', [
                'ERROR' => $e->getMessage()
            ]));
            return false;
        }
    }

    public function InstallFiles()
    {
        global $APPLICATION;
        try {
            $installer = new FileInstaller(__DIR__ . '/../', Application::getDocumentRoot());
            $installer->install();
        } catch (\Bitrix\Main\SystemException $e) {
            $APPLICATION->ResetException();
            $APPLICATION->ThrowException(Loc::getMessage('MAXIMASTER.COUPANDA:FILES_INSTALLATION_ERROR', [
                'ERROR' => $e->getMessage()
            ]));
            return false;
        }

        return true;
    }

    public function InstallEvents()
    {
        return true;
    }

    public function UninstallDB()
    {
        $connection = Application::getConnection();
        $installer = new DatabaseInstaller($this->MODULE_ID, $connection);

        try {
            return $installer->uninstall();
        } catch (\Exception $e) {
            global $APPLICATION;
            $APPLICATION->ResetException();
            $APPLICATION->ThrowException(Loc::getMessage('MAXIMASTER.COUPANDA:DB_UNINSTALLATION_ERROR', [
                'ERROR' => $e->getMessage()
            ]));
            return false;
        }
    }

    public function UninstallFiles()
    {
        global $APPLICATION;
        try {
            $installer = new FileInstaller(__DIR__ . '/../', Application::getDocumentRoot());
            $installer->uninstall();
        } catch (\Bitrix\Main\SystemException $e) {
            $APPLICATION->ResetException();
            $APPLICATION->ThrowException(Loc::getMessage('MAXIMASTER.COUPANDA:FILES_UNINSTALLATION_ERROR', [
                'ERROR' => $e->getMessage()
            ]));
            return false;
        }

        return true;
    }

    public function UninstallEvents()
    {
        return true;
    }
}
