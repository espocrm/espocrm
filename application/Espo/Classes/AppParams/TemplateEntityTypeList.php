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

namespace Espo\Classes\AppParams;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\Template;
use Espo\Tools\App\AppParam;
use RuntimeException;

/**
 * Returns a list of entity types for which a PDF template exists.
 *
 * @noinspection PhpUnused
 */
class TemplateEntityTypeList implements AppParam
{

    public function __construct(
        private Acl $acl,
        private SelectBuilderFactory $selectBuilderFactory,
        private EntityManager $entityManager,
    ) {}

    /**
     * @return string[]
     */
    public function get(): array
    {
        if (!$this->acl->checkScope(Template::ENTITY_TYPE)) {
            return [];
        }

        $list = [];

        try {
            $query = $this->selectBuilderFactory
                ->create()
                ->from(Template::ENTITY_TYPE)
                ->withAccessControlFilter()
                ->buildQueryBuilder()
                ->select(['entityType'])
                ->where(['status' => Template::STATUS_ACTIVE])
                ->group(['entityType'])
                ->build();
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException('', 0, $e);
        }

        $templateCollection = $this->entityManager
            ->getRDBRepositoryByClass(Template::class)
            ->clone($query)
            ->find();

        foreach ($templateCollection as $template) {
            $list[] = $template->getTargetEntityType();
        }

        return $list;
    }
}
