<?php

namespace Maximaster\Coupanda\Process;

use Bitrix\Main\DB\DbException;
use Bitrix\Main\Entity\Query;
use Maximaster\Coupanda\Orm\ProcessTable;

class ProcessRepository
{
    /**
     * @param Query $query
     * @return Process[]
     */
    public static function find(Query $query)
    {
        $result = $query->exec();
        $processList = [];
        while ($process = $result->fetch()) {
            $processList[] = Process::fromState($process);
        }

        return $processList;
    }

    /**
     * @param Query $query
     * @return Process
     */
    public static function findOne(Query $query)
    {
        $query->setLimit(1);
        return static::find($query)[0];
    }

    public static function findById($id)
    {
        return static::find(static::getByIdQuery($id));
    }

    public static function findOneById($id)
    {
        return static::findOne(static::getByIdQuery($id));
    }

    protected static function getByIdQuery($id)
    {
        return ProcessTable::query()->addFilter('ID', $id)->setSelect(['*']);
    }

    public static function save(Process $process)
    {
        $processData = Process::toState($process);

        $result = $process->getId() > 0 ?
            ProcessTable::update($process->getId(), $processData) :
            ProcessTable::add($processData);

        if (!$result->isSuccess()) {
            throw new DbException(implode('. ', $result->getErrorMessages()));
        }

        if (!$process->getId()) {
            $process->setId($result->getId());
        }

        return $result;
    }
}