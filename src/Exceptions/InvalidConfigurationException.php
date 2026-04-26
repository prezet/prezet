<?php

namespace Prezet\Prezet\Exceptions;

use Exception;

class InvalidConfigurationException extends Exception
{
    public function __construct(string $configKey, mixed $configValue, string $deficiency)
    {
        $message = 'Invalid configuration for '.$configKey.'. The given value '.$this->formatValue($configValue).' '.$deficiency;

        parent::__construct($message);
    }

    private function formatValue(mixed $value): string
    {
        if (is_scalar($value) || is_null($value)) {
            return var_export($value, true);
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return get_debug_type($value);
    }
}
