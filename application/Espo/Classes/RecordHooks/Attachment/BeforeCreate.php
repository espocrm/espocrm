<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Classes\RecordHooks\Attachment;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\Tools\Attachment\Checker;
use Espo\Tools\Attachment\DetailsObtainer;

/**
 * @implements SaveHook<Attachment>
 */
class BeforeCreate implements SaveHook
{
    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private DetailsObtainer $detailsObtainer,
        private Checker $checker
    ) {}

    public function process(Entity $entity): void
    {
        $this->processStorage($entity);
        $this->processRole($entity);
        $this->processSize($entity);

        $this->checker->checkType($entity);
    }

    private function processStorage(Attachment $entity): void
    {
        $storage = $entity->getStorage();

        $availableStorageList = $this->config->get('attachmentAvailableStorageList') ?? [];

        if (
            $storage &&
            (
                !in_array($storage, $availableStorageList) ||
                !$this->metadata->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])
            )
        ) {
            $entity->clear('storage');
        }
    }

    /**
     * @throws Forbidden
     */
    private function processSize(Attachment $entity): void
    {
        $size = $entity->getSize();

        $maxSize = $this->detailsObtainer->getUploadMaxSize($entity);

        // Checking not actual file size but a set value.
        if ($size && $size > $maxSize) {
            throw new Forbidden("Attachment size exceeds `attachmentUploadMaxSize`.");
        }
    }

    private function processRole(Attachment $entity): void
    {
        if (!$entity->getRole()) {
            $entity->setRole(Attachment::ROLE_ATTACHMENT);
        }
    }
}
