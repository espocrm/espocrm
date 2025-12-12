<?php

namespace Espo\Core\Rebuild\Actions;

use Espo\Core\Rebuild\RebuildAction;
use Espo\Tools\Currency\RecordManager;

/**
 * @noinspection PhpUnused
 */
class SyncCurrency implements RebuildAction
{
    public function __construct(
        private RecordManager $recordManager,
    ) {}

    public function process(): void
    {
        $this->recordManager->sync();
    }
}
