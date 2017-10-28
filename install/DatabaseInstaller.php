<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\DB\Connection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Maximaster\Coupanda\Orm\ProcessTable;

class DatabaseInstaller
{
    protected $moduleId;

    public function __construct($moduleId, Connection $connection)
    {
        Loc::loadMessages(__FILE__);
        $this->moduleId = $moduleId;
        $this->connection = $connection;
    }

    public function install()
    {
        try {
            $this->connection->startTransaction();
            ModuleManager::registerModule($this->moduleId);
            Loader::includeModule($this->moduleId);
            $this->createProcessTable();
            $this->addPIDColumn();
            $this->connection->commitTransaction();
        } catch (\Exception $e) {
            $this->connection->rollbackTransaction();
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return true;
    }

    public function uninstall()
    {
        try {
            Loader::includeModule($this->moduleId);
            $this->connection->startTransaction();
            $this->dropProcessTable();
            $this->dropPIDColumn();
            ModuleManager::unRegisterModule($this->moduleId);
            $this->connection->commitTransaction();
        } catch (\Exception $e) {
            $this->connection->rollbackTransaction();
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return true;
    }

    protected function createProcessTable()
    {
        $tableName = ProcessTable::getTableName();
        if (!$this->connection->isTableExists($tableName)) {
            $this->connection->createTable(ProcessTable::getTableName(),
                ProcessTable::getMap(), ['ID'], ['ID']);
        }
    }

    protected function dropProcessTable()
    {
        $tableName = ProcessTable::getTableName();
        if ($this->connection->isTableExists($tableName)) {
            $this->connection->dropTable($tableName);
        }
    }

    protected function addPIDColumn()
    {
        $this->connection->queryExecute('ALTER TABLE `b_sale_discount_coupon` ADD `MAXIMASTER_COUPANDA_PID` INT NULL DEFAULT NULL');
    }

    protected function dropPIDColumn()
    {
        $this->connection->queryExecute('ALTER TABLE `b_sale_discount_coupon` DROP `MAXIMASTER_COUPANDA_PID`');
    }
}
