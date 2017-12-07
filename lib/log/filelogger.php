<?php

namespace Maximaster\Coupanda\Log;

class FileLogger extends AbstractLogger
{
    protected $onlyErrors = true;
    protected $errorLevels = [
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::EMERGENCY,
        LogLevel::ERROR,
        LogLevel::WARNING
    ];

    protected function isErrorLevel($level)
    {
        return in_array($level, $this->errorLevels);
    }

    public function __construct($onlyErrors = false)
    {
        $this->onlyErrors = $onlyErrors === true;
    }

    public function log($level, $message, array $context = [])
    {
        if ($this->onlyErrors) {
            if (!$this->isErrorLevel($level)) {
                return;
            }
        }

        $date = date('Y-m-d H:i:s');
        if (!empty($context)) {
            $newContext = [];
            foreach ($context as $key => $value) {
                if (!is_scalar($value)) {
                    $value = print_r($value, true);
                }

                $newContext[ '{' . $key . '}' ] = $value;
            }
        }

        $message = str_replace(array_keys($newContext), array_values($newContext), $message);
        $message = $date . ' [' . $level . '] ' . $message . PHP_EOL;
        file_put_contents(__DIR__ . '/../../debug.log', $message, FILE_APPEND);
    }
}
