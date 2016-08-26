<?php

namespace tests\testData\Hooks\testCase1\custom\Espo\Custom\Hooks\Note;

class Mentions extends \Espo\Hooks\Note\Mentions
{
    public static $order = 7;

    public function beforeSave(\Espo\ORM\Entity $entity)
    {

    }
}