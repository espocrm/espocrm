<?php

namespace tests\unit\testData\Hooks\testCase3\application\Espo\Hooks\Note;

class Mentions
{
    public static $order = 9;

    public function beforeSave(\Espo\ORM\Entity $entity, array $options): void
    {

    }
}
