<?php

namespace Espo\Classes\FieldProcessing\CurrencyRecord;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\Utils\Metadata;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\Entity;
use ValueError;

/**
 * @implements Loader<CurrencyRecord>
 */
class Symbol implements Loader
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        try {
            $code = $entity->getCode();
        } catch (ValueError) {
            return;
        }
        $symbol = $this->metadata->get("app.currency.symbolMap.$code");

        $entity->setSymbol($symbol);
    }
}
