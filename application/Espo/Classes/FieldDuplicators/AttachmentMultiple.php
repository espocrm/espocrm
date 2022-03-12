<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Classes\FieldDuplicators;

use Espo\Core\Record\Duplicator\FieldDuplicator;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Entities\Attachment;

use stdClass;

class AttachmentMultiple implements FieldDuplicator
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function duplicate(Entity $entity, string $field): stdClass
    {
        $valueMap = (object) [];

        /** @var \Espo\ORM\Collection<Attachment> $attachmentList */
        $attachmentList = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, $field)
            ->find();

        if (is_countable($attachmentList) && !count($attachmentList)) {
            return $valueMap;
        }

        $idList = [];
        $nameHash = (object) [];
        $typeHash = (object) [];

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

        foreach ($attachmentList as $attachment) {
            $copiedAttachment = $attachmentRepository->getCopiedAttachment($attachment);

            $copiedAttachment->set('field', $field);

            $this->entityManager->saveEntity($copiedAttachment);

            $idList[] = $copiedAttachment->getId();

            $nameHash->{$copiedAttachment->getId()} = $copiedAttachment->getName();
            $typeHash->{$copiedAttachment->getId()} = $copiedAttachment->getType();
        }

        $valueMap->{$field . 'Ids'} = $idList;
        $valueMap->{$field . 'Names'} = $nameHash;
        $valueMap->{$field . 'Types'} = $typeHash;

        return $valueMap;
    }
}
