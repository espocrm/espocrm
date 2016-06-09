<?php

namespace Espo\Core\Utils\FieldManager\Hooks;

class NumberType extends Base
{
    public function onRead($scope, $name, &$defs)
    {
        $number = $this->getEntityManager()->getRepository('NextNumber')->where(array(
            'entityType' => $scope,
            'fieldName' => $name
        ))->findOne();

        $value = null;
        if (!$number) {
            $value = 1;
        } else {
            if (!$number->get('value')) {
                $value = 1;
            }
        }

        if (!$value && $number) {
            $value = $number->get('value');
        }

        $defs['nextNumber'] = $value;
    }

    public function afterSave($scope, $name, $defs)
    {
        if (!isset($defs['nextNumber'])) return;

        $number = $this->getEntityManager()->getRepository('NextNumber')->where(array(
            'entityType' => $scope,
            'fieldName' => $name
        ))->findOne();

        if (!$number) {
            $number = $this->getEntityManager()->getEntity('NextNumber');

            $number->set('entityType', $scope);
            $number->set('fieldName', $name);
        }

        $number->set('value', $defs['nextNumber']);
        $this->getEntityManager()->saveEntity($number);
    }

    public function afterRemove($scope, $name)
    {
        $number = $this->getEntityManager()->getRepository('NextNumber')->where(array(
            'entityType' => $scope,
            'fieldName' => $name
        ))->findOne();

        if (!$number) return;

        $this->getEntityManager()->removeEntity($number);
    }
}