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

namespace Espo\Core\Record\Formula;

use Espo\Core\Formula\Exceptions\Error as FormulaError;
use Espo\Core\Formula\Manager as FormulaManager;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use RuntimeException;
use stdClass;

/**
 * Formula script processing for API requests.
 */
class Processor
{
    public function __construct(
        private FormulaManager $formulaManager,
        private Metadata $metadata
    ) {}

    /**
     * Process a before-create formula script.
     */
    public function processBeforeCreate(Entity $entity, CreateParams $params): void
    {
        $script = $this->getScript($entity->getEntityType());

        if (!$script) {
            return;
        }

        $variables = (object) [
            '__skipDuplicateCheck' => $params->skipDuplicateCheck(),
            '__isRecordService' => true,
        ];

        $this->run($script, $entity, $variables);
    }

    /**
     * Process a before-update formula script.
     */
    public function processBeforeUpdate(Entity $entity, UpdateParams $params): void
    {
        $script = $this->getScript($entity->getEntityType());

        if (!$script) {
            return;
        }

        $variables = (object) [
            '__skipDuplicateCheck' => $params->skipDuplicateCheck(),
            '__isRecordService' => true,
        ];

        $this->run($script, $entity, $variables);
    }

    private function run(string $script, Entity $entity, stdClass $variables): void
    {
        try {
            $this->formulaManager->run($script, $entity, $variables);
        } catch (FormulaError $e) {
            throw new RuntimeException('Formula script error.', 500, $e);
        }
    }

    private function getScript(string $entityType): ?string
    {
        /** @var ?string */
        return $this->metadata->get(['formula', $entityType, 'beforeSaveApiScript']);
    }
}
