<?php

namespace tests\unit\testData\Hooks\testCase1\application\Espo\Modules\Crm\Hooks\Note;

class Mentions
{
    public static int $order = 9;

    public function beforeSave(\Espo\ORM\Entity $entity, array $options): void
    {

    }
}
