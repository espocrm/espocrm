<?php

namespace Espo\Core\Formula\Functions\EntityGroup;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

class IsAttributeChangedType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        $attributeList = [];
        if (is_array($item->value)) {
            $attributeList = $attribute;
            foreach ($item->value as $value) {
                $attribute = $this->evaluate($item->value);
                $attributeList[] = $attribute;
            }
        } else {
            $attribute = $this->evaluate($item->value);
            $attributeList[] = $attribute;
        }

        return $this->check($attributeList);
    }

    protected function check(array $attributeList)
    {
        $result = true;
        foreach ($attributeList as $i => $attribute) {
            if (!$this->getEntity()->isAttributeChanged($attribute)) {
                $result = false;
                break;
            }
        }
        return $result;
    }
}