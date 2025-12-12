<?php

namespace Espo\Entities;

use Espo\Core\ORM\Entity;

class CurrencyRecord extends Entity
{
    public const string ENTITY_TYPE = 'CurrencyRecord';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_CODE = 'code';

    public const string STATUS_ACTIVE = 'Active';

    public function getCode(): string
    {
        return $this->get(self::FIELD_CODE);
    }

    public function setCode(string $code): self
    {
        return $this->set(self::FIELD_CODE, $code);
    }

    public function getStatus(): string
    {
        return $this->get(self::FIELD_STATUS);
    }

    public function setStatus(string $status): self
    {
        return $this->set(self::FIELD_STATUS, $status);
    }

    public function setLabel(?string $label): self
    {
        return $this->set('label', $label);
    }

    public function setIsBase(bool $isBase): self
    {
        return $this->set('isBase', $isBase);
    }
}
