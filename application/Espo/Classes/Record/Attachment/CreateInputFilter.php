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

namespace Espo\Classes\Record\Attachment;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Input\Data;
use Espo\Core\Record\Input\Filter;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Tools\Attachment\AccessChecker;
use Espo\Tools\Attachment\DetailsObtainer;
use Espo\Tools\Attachment\FieldData;

/**
 * @noinspection PhpUnused
 */
class CreateInputFilter implements Filter
{
    public function __construct(
        private EntityManager $entityManager,
        private AccessChecker $accessChecker,
        private DetailsObtainer $detailsObtainer
    ) {}

    /**
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     */
    public function filter(Data $data): void
    {
        $data->clear('parentId');
        $data->clear('relatedId');

        $contents = $this->handleContents($data);

        $relatedEntityType = $this->getRelatedEntityType($data);

        $field = $data->get('field');
        $role = $data->get('role') ?? Attachment::ROLE_ATTACHMENT;

        if (!$relatedEntityType || !$field) {
            throw new BadRequest("No `field` and `parentType`.");
        }

        $fieldData = new FieldData($field, $data->get('parentType'), $data->get('relatedType'));

        $this->accessChecker->check($fieldData, $role);
        $this->checkMaxSize($contents, $data, $field, $role);
    }

    private function getRelatedEntityType(Data $data): ?string
    {
        if ($data->get('parentType') !== null) {
            $data->clear('relatedType');

            return $data->get('parentType');
        }

        if ($data->get('relatedType') !== null) {
            return $data->get('relatedType');
        }

        return null;
    }

    /**
     * @throws BadRequest
     */
    private function handleContents(Data $data): string
    {
        $isBeingUploaded = $data->get('isBeingUploaded') ?? false;

        $contents = '';

        if (!$isBeingUploaded) {
            if (!$data->has('file')) {
                throw new BadRequest("No file contents.");
            }

            $file = $data->get('file');

            if (!is_string($file)) {
                throw new BadRequest("Non-string file contents.");
            }

            $arr = explode(',', $file);

            if (count($arr) < 2) {
                throw new BadRequest("Bad file contents.");
            }

            $contents = base64_decode($arr[1]);

            if ($contents === false) {
                throw new BadRequest("Could not decode file contents.");
            }
        }

        $data->set('contents', $contents);

        return $contents;
    }

    /**
     * @throws BadRequest
     */
    private function checkMaxSize(string $contents, Data $data, mixed $field, mixed $role): void
    {
        $size = mb_strlen($contents, '8bit');

        $dummy = $this->entityManager->getRepositoryByClass(Attachment::class)->getNew();

        $dummy->set([
            'parentType' => $data->get('parentType'),
            'relatedType' => $data->get('relatedType'),
            'field' => $field,
            'role' => $role,
        ]);

        $maxSize = $this->detailsObtainer->getUploadMaxSize($dummy);

        if ($maxSize && $size > $maxSize * 1024 * 1024) {
            throw new BadRequest("File size should not exceed $maxSize Mb.");
        }
    }
}
