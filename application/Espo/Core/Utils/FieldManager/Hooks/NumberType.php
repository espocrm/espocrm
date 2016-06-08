<?php

namespace Espo\Core\Utils\FieldManager\Hooks;

class NumberType extends Base
{
    public function onRead($scope, $name, &$defs)
    {
        $defs['nextNumber'] = 2;
    }

    public function afterSave($scope, $name, $defs)
    {

    }
}