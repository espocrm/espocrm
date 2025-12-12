<?php

namespace Espo\Classes\FieldProcessing\CurrencyRecord;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\Entity;
use ValueError;

/**
 * @implements Loader<CurrencyRecord>
 */
class IsBase implements Loader
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
    ) {}


    public function process(Entity $entity, Params $params): void
    {
        try {
            $code = $entity->getCode();
        } catch (ValueError) {
            $entity->setIsBase(false);

            return;
        }

        $isBase = $code === $this->configDataProvider->getBaseCurrency();

        $entity->setIsBase($isBase);
    }
}
