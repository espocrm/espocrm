<?php

namespace Espo\Core\Formula\Functions\EnvGroup;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

class UserAttributeType extends \Espo\Core\Formula\Functions\AttributeType
{
    protected function init()
    {
        $this->addDependency('user');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
            throw new Error();
        }

        $attribute = $this->evaluate($item->value[0]);

        if (!is_string($attribute)) {
            throw new Error();
        }

        return $this->getInjection('user')->get($attribute);
    }
}