<?php

namespace Maximaster\Coupanda\Process;

use Bitrix\Main\Type\DateTime;

class Process
{
    /** @var int */
    protected $id;

    /** @var DateTime */
    protected $startedAt;

    /** @var DateTime */
    protected $finishedAt;

    /** @var int */
    protected $processedCount = 0;

    /** @var ProcessSettings */
    protected $settings;

    public static function fromState(array $state)
    {
        $instance = new static();
        isset($state['ID']) && $instance->setId($state['ID']);
        isset($state['STARTED_AT']) && $instance->setStartedAt($state['STARTED_AT']);
        isset($state['FINISHED_AT']) && $instance->setFinishedAt($state['FINISHED_AT']);
        isset($state['PROCESSED_COUNT']) && $instance->setProcessedCount($state['PROCESSED_COUNT']);
        isset($state['SETTINGS']) && $instance->setSettings($state['SETTINGS']);
        return $instance;
    }

    public static function toState(Process $process)
    {
        return [
            'ID' => $process->getId(),
            'STARTED_AT' => $process->getStartedAt(),
            'FINISHED_AT' => $process->getFinishedAt(),
            'PROCESSED_COUNT' => $process->getProcessedCount(),
            'SETTINGS' => $process->getSettings(),
        ];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param DateTime $startedAt
     */
    public function setStartedAt(DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param DateTime $finishedAt
     */
    public function setFinishedAt(DateTime $finishedAt = null)
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * @return int
     */
    public function getProcessedCount()
    {
        return $this->processedCount;
    }

    /**
     * @param int $processedCount
     */
    public function setProcessedCount($processedCount)
    {
        $this->processedCount = (int)$processedCount;
    }

    /**
     * @return ProcessSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param ProcessSettings $settings
     */
    public function setSettings(ProcessSettings $settings)
    {
        $this->settings = $settings;
    }

    public function getProgressPercentage()
    {
        $percentage = $this->getProcessedCount() * 100 / $this->getSettings()->getCount();
        return $percentage;
    }

    public function isInProgress()
    {
        return $this->getSettings()->getCount() > $this->getProcessedCount();
    }

    public function incrementProcessedCount($count = 1)
    {
        $this->setProcessedCount(
            $this->getProcessedCount() + $count
        );
    }
}
