<?php

namespace Espo\Hooks\Email;

use Espo\Core\Hook\Hook\AfterRemove;
use Espo\Entities\Email;
use Espo\Entities\Note;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\RemoveOptions;

/**
 * @implements AfterRemove<Email>
 */
class NoteRemove implements AfterRemove
{
    private const int LIMIT = 5;

    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        $notes = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->sth()
            ->where([
                'relatedId' => $entity->getId(),
                'relatedType' => $entity->getEntityType(),
                'type' => [
                    Note::TYPE_EMAIL_RECEIVED,
                    Note::TYPE_EMAIL_SENT,
                ],
            ])
            ->limit(self::LIMIT)
            ->find();

        foreach ($notes as $note) {
            $this->entityManager->removeEntity($note);
        }
    }
}
