<?php

namespace Espo\Tools\Export\Processors\Xlsx\CellValuePreparators;

use Espo\Core\Field\Date as DateValue;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;

class Date implements CellValuePreparator
{
    public function prepare(string $entityType, string $name, array $data): ?DateValue
    {
        $value = $data[$name] ?? null;

        if (!$value) {
            return null;
        }

        return DateValue::fromString($value);
    }
}
