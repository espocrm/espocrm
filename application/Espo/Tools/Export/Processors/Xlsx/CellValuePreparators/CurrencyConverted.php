<?php

namespace Espo\Tools\Export\Processors\Xlsx\CellValuePreparators;

use Espo\Core\Field\Currency as CurrencyValue;
use Espo\Core\Utils\Config;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;

class CurrencyConverted implements CellValuePreparator
{
    private string $code;

    public function __construct(Config $config)
    {
        $this->code = $config->get('defaultCurrency');
    }

    public function prepare(string $entityType, string $name, array $data): ?CurrencyValue
    {
        $value = $data[$name] ?? null;

        if ($value === null) {
            return null;
        }

        return CurrencyValue::create($value, $this->code);
    }
}
