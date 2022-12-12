<?php

namespace Espo\Tools\Export\Processors\Xlsx\CellValuePreparators;

use Espo\Core\Field\Currency as CurrencyValue;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;

class Currency implements CellValuePreparator
{
    public function prepare(string $entityType, string $name, array $data): ?CurrencyValue
    {
        $code = $data[$name . 'Currency'] ?? null;
        $value = $data[$name] ?? null;

        if (!$code || $value === null) {
            return null;
        }

        return CurrencyValue::create($value, $code);
    }
}
