<?php

namespace Maximaster\Coupanda\Process;

class ProcessReport
{
    protected $progress;

    public function __construct(ProcessProgress $progress)
    {
        $this->progress = $progress;
    }

    public function getStartedAt()
    {
        $started = $this->progress->getStartDate();
        return $started ? $started->format('d.m.Y H:i:s') : null;
    }

    public function getFinishedAt()
    {
        $finished = $this->progress->getFinishDate();
        return $finished ? $finished->format('d.m.Y H:i:s') : null;
    }

    public function getCount()
    {
        return $this->progress->getProcessedCount();
    }

    public function asArray()
    {
        $report = [
            [
                'code' => 'started_at',
                'name' => 'Дата начала',
                'value' => $this->getStartedAt(),
            ],
            [
                'code' => 'finished_at',
                'name' => 'Дата окончания',
                'value' => $this->getFinishedAt(),
            ],
            [
                'code' => 'count',
                'name' => 'Количество',
                'value' => $this->getCount(),
            ]
        ];

        return $report;
    }
}
