<?php

namespace Espo\Custom\Hooks\TargetList;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\ORM\EntityManager;
use Espo\Entities\NextNumber;

/**
 * @implements BeforeSave<\Espo\Modules\Crm\Entities\TargetList>
 */
class SequenceNumber implements BeforeSave
{
    public static int $order = 8;

    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew()) {
            return;
        }

        if ($entity->get('cTargetListNumber')) {
            return;
        }

        $sequenceNumber = $this->generateSequenceNumber();
        $entity->set('cTargetListNumber', $sequenceNumber);
    }

    private function generateSequenceNumber(): string
    {
        return $this->entityManager->getTransactionManager()->run(
            function () {
                $entityType = 'TargetList';
                $fieldName = 'sequenceNumber';
                $prefix = 'TL';

                $nextNumber = $this->entityManager
                    ->getRDBRepository(NextNumber::ENTITY_TYPE)
                    ->where([
                        'entityType' => $entityType,
                        'fieldName' => $fieldName,
                    ])
                    ->forUpdate()
                    ->findOne();

                if (!$nextNumber) {
                    $nextNumber = $this->entityManager->getNewEntity(NextNumber::ENTITY_TYPE);
                    $nextNumber->set('entityType', $entityType);
                    $nextNumber->set('fieldName', $fieldName);
                    $nextNumber->set('value', 1);
                }

                $value = $nextNumber->get('value') ?? 1;
                $formatted = $prefix . date('dmY') . str_pad((string) $value, 5, '0', STR_PAD_LEFT);

                $nextNumber->set('value', $value + 1);
                $this->entityManager->saveEntity($nextNumber);

                return $formatted;
            }
        );
    }
}
