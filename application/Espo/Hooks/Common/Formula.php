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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Formula\Manager as FormulaManager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;

use Exception;
use stdClass;

/**
 * @implements BeforeSave<Entity>
 */
class Formula implements BeforeSave
{
    public static int $order = 11;

    public function __construct(
        private Metadata $metadata,
        private FormulaManager $formulaManager,
        private Log $log
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($options->get('skipFormula')) {
            return;
        }

        $scriptList = $this->metadata->get(['formula', $entity->getEntityType(), 'beforeSaveScriptList'], []);

        $variables = (object) [];

        foreach ($scriptList as $script) {
            $this->runScript($script, $entity, $variables);
        }

        $customScript = $this->metadata->get(['formula', $entity->getEntityType(), 'beforeSaveCustomScript']);

        if (!$customScript) {
            return;
        }

        $this->runScript($customScript, $entity, $variables);
    }

    private function runScript(string $script, Entity $entity, stdClass $variables): void
    {
        try {
            $this->formulaManager->run($script, $entity, $variables);
        } catch (Exception $e) {
            $this->log->critical('Before-save formula script failed. {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
