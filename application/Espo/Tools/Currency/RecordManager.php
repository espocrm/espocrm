<?php

namespace Espo\Tools\Currency;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\ORM\EntityManager;

class RecordManager
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
    ) {}

    public function sync(): void
    {

    }
}
