<?php

namespace Espo\Tools\Currency;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\UpdateBuilder;

class RecordManager
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
    ) {}

    public function sync(): void
    {
        $this->entityManager->getTransactionManager()->run(function () {
            $this->syncInTransaction();
        });
    }

    private function syncInTransaction(): void
    {
        $this->lock();

        foreach ($this->configDataProvider->getCurrencyList() as $code) {
            $this->syncCode($code);
        }

        $this->deactivateNotListed();
    }

    private function syncCode(string $code): void
    {
        $record = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->where([CurrencyRecord::FIELD_CODE => $code])
            ->findOne();

        if (!$record) {
            $record = $this->entityManager->getRDBRepositoryByClass(CurrencyRecord::class)->getNew();

            $record->setCode($code);
        }

        $record->setStatus(CurrencyRecord::STATUS_ACTIVE);

        $this->entityManager->saveEntity($record);
    }

    private function deactivateNotListed(): void
    {
        $list = $this->configDataProvider->getCurrencyList();

        $updateQuery = UpdateBuilder::create()
            ->in(CurrencyRecord::ENTITY_TYPE)
            ->set([
                CurrencyRecord::FIELD_STATUS => CurrencyRecord::STATUS_INACTIVE,
            ])
            ->where([
                CurrencyRecord::FIELD_CODE . '!=' => $list,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);
    }

    private function lock(): void
    {
        $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->forUpdate()
            ->sth()
            ->find();
    }
}
