<?php

namespace Espo\Tools\Export\Processors\Xlsx\CellValuePreparators;

use Espo\Core\Field\Address as AddressValue;
use Espo\Core\Field\Address\AddressFormatterFactory;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;

class Address implements CellValuePreparator
{
    public function __construct(
        private AddressFormatterFactory $formatterFactory
    ) {}

    public function prepare(string $entityType, string $name, array $data): ?string
    {
        $address = AddressValue::createBuilder()
            ->setStreet($data[$name . 'Street'] ?? null)
            ->setCity($data[$name . 'City'] ?? null)
            ->setState($data[$name . 'State'] ?? null)
            ->setCountry($data[$name . 'Country'] ?? null)
            ->setPostalCode($data[$name . 'PostalCode'] ?? null)
            ->build();

        $formatter = $this->formatterFactory->createDefault();

        return $formatter->format($address) ?: null;
    }
}
