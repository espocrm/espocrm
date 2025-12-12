<?php

namespace Espo\Classes\FieldProcessing\CurrencyRecord;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\Entity;
use ValueError;

/**
 * @implements Loader<CurrencyRecord>
 */
class Label implements Loader
{
    public function __construct(
        private Metadata $metadata,
        private Language $defaultLanguage
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        try {
            $code = $entity->getCode();
        } catch (ValueError) {
            return;
        }

        $name = $this->defaultLanguage->translateLabel($code, 'names', 'Currency');

        $symbol = $this->metadata->get("app.currency.symbolMap.$code");

        if ($symbol) {
            $name .= ' ' . $symbol;
        }

        $entity->setLabel($name);
    }
}
