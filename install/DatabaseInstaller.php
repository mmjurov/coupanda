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
        /**
         * Если вдруг у нас отсутствует движок транзакций для каких-то таблиц, то:
         * - возникновение ошибки в момент регистрации модуля приведет к тому, что будет выброшено исключение, и
         * регистрация модуля будет отмененеа в блоке catch
         * - возникновение ошибки в момент создания таблицы (например по причине отсутствия прав) также приведет к
         * исключению, и тогда снова регистрация будет снята в блоке catch
         * - возникновение ошибки при модификации колонки в существующей таблице скажет о том, что либо такая колонка
         * уже существует (и модуль был вообще ранее установлен, что само по себе крайне исключительная ситуация), либо
         * пользователю субд не хватило прав на добавление колонки, что тоже довольно редкий кейс, т.к. сам битрикс
         * нормально работать в таких условиях не будет
         */
        try {
            $this->connection->startTransaction();
            ModuleManager::registerModule($this->moduleId);
            Loader::includeModule($this->moduleId);
            $this->createProcessTable();
            $this->addPIDColumn();
            $this->addEventHandlers();
            $this->connection->commitTransaction();
        } catch (\Exception $e) {
            $this->connection->rollbackTransaction();
            ModuleManager::unRegisterModule($this->moduleId);
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return true;
    }

    public function uninstall()
    {
        /**
         * Если вдруг отсутствует движок транзакций, то:
         * - возникновение исключения при удалении таблицы приведет к тому, что модуль останется установленным
         * - возникновение исключения в процессе изменения таблицы практически исключено, т.к. битрикс сам работать в таких
         * условиях не сможет
         * - возникновение исключения в процессе снятия регистрации модуля - это единственная ситуция, в которой деинсталлятор
         * может отработать некорректно, но данная ситуация видится мне невозможной, если только специально не вмешаться
         * в процесс снятия регистрации модуля
         */
        try {
            Loader::includeModule($this->moduleId);
            $this->connection->startTransaction();
            $this->dropProcessTable();
            $this->dropPIDColumn();
            $this->dropEventHandlers();
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

    protected function addEventHandlers()
    {
        \RegisterModuleDependences(
            'main',
            'OnPageStart',
            $this->moduleId,
            EventHandlersRegistry::class,
            'register',
            100,
            __DIR__ . '/../lib/eventhandlersregistry.php'
        );
    }

    protected function dropEventHandlers()
    {
        \UnRegisterModuleDependences(
            'main',
            'OnPageStart',
            $this->moduleId,
            EventHandlersRegistry::class,
            'register',
            __DIR__ . '/../lib/eventhandlersregistry.php'
        );
    }
}
