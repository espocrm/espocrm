<?php

namespace Espo\Tools\Export\Processors\Xlsx\CellValuePreparators;

use Espo\Core\Field\DateTime as DateTimeValue;
use Espo\Core\Utils\Config;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;

use DateTimeZone;

class DateTime implements CellValuePreparator
{
    private string $timezone;

    public function __construct(Config $config)
    {
        $this->timezone = $config->get('timeZone') ?? 'UTC';
    }

    public function prepare(string $entityType, string $name, array $data): ?DateTimeValue
    {
        $value = $data[$name] ?? null;

        if (!$value) {
            return null;
        }

        return DateTimeValue::fromString($value)
            ->withTimezone(
                new DateTimeZone($this->timezone)
            );
    }
}
