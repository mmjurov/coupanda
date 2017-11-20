<?php

namespace Maximaster\Coupanda\Process;

class ProcessProgress
{
    private $key = 'MAXIMASTER.COUPANDA.PROCESS.SESSION.STORAGE';
    protected $settings;

    public function __construct()
    {
        if (!$this->storageExists()) {
            $this->createNewStorage();
        };
    }

    protected function storageExists()
    {
        return isset($_SESSION[$this->key]);
    }

    protected function setValue($key, $value)
    {
        $_SESSION[$this->key][$key] = $value;
    }

    public function getValue($key)
    {
        return $_SESSION[$this->key][$key];
    }

    protected function createNewStorage()
    {
        $_SESSION[$this->key] = [];
        return $_SESSION[$this->key];
    }

    public function getStep()
    {
        return $this->getValue('step');
    }

    public function incrementStep()
    {
        $this->setValue('step', $this->getValue('step') + 1);
    }

    public function getProcessedCount()
    {
        return $this->getValue('processed_count');
    }

    public function incrementProcessedCount($count = 1)
    {
        $this->setValue('processed_count', $this->getValue('processed_count') + $count);
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

    public function clear()
    {
        $this->createNewStorage();
    }

    public function setStartDate(\DateTime $dateTime)
    {
        $this->setValue('start_date', $dateTime);
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->getValue('start_date');
    }

    public function setFinishDate(\DateTime $dateTime)
    {
        $this->setValue('finish_date', $dateTime);
    }

    /**
     * @return \DateTime
     */
    public function getFinishDate()
    {
        return $this->getValue('finish_date');
    }

    public function setSettings(ProcessSettings $settings)
    {
        $this->settings = $settings;
        $this->setValue('settings', serialize($settings));
    }

    /**
     * @return ProcessSettings
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            $settings = unserialize($this->getValue('settings'));
            if ($settings instanceof ProcessSettings) {
                $this->settings = $settings;
            }
        }

        return $this->settings;

    }
}