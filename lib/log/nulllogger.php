<?php

namespace Maximaster\Coupanda\Log;

class NullLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        // noop
    }
}