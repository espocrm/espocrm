<?php

namespace tests\testData\Hooks\testCase1\application\Espo\Hooks\Note;

class Mentions extends \Espo\Core\Hooks\Base
{
    public static $order = 9;

    public function beforeSave(\Espo\ORM\Entity $entity)
    {

    }
}
