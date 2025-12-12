<?php

namespace Espo\Entities;

use Espo\Core\ORM\Entity;

class CurrencyRate extends Entity
{
    public const string ENTITY_TYPE = 'CurrencyRate';

    /**
     * @return numeric-string
     */
    public function getRate(): string
    {
        /** @var numeric-string */
        return $this->get('rate') ?? '1';
    }

    /**
     * @param numeric-string $rate
     */
    public function setRate(string $rate): self
    {
        return $this->set('rate', $rate);
    }
}
