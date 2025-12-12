<?php

namespace Espo\Classes\FieldProcessing\CurrencyRecord;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\Entity;

/**
 * @implements Loader<CurrencyRecord>
 */
class Rate implements Loader
{
    public function process(Entity $entity, Params $params): void
    {
        // @todo
    }
}
