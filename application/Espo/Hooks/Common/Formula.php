<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

use Espo\Core\{
    Utils\Metadata,
    Formula\Manager as FormulaManager,
    Utils\Log,
};

use Exception;

class Formula
{
    public static $order = 11;

    private $metadata;

    private $formulaManager;

    private $log;

    public function __construct(Metadata $metadata, FormulaManager $formulaManager, Log $log)
    {
        $this->metadata = $metadata;
        $this->formulaManager = $formulaManager;
        $this->log = $log;
    }

    public function beforeSave(Entity $entity, array $options = [])
    {
        if (!empty($options['skipFormula'])) {
            return;
        }

        $scriptList = $this->metadata->get(['formula', $entity->getEntityType(), 'beforeSaveScriptList'], []);

        $variables = (object) [];

        foreach ($scriptList as $script) {
            try {
                $this->formulaManager->run($script, $entity, $variables);
            }
            catch (Exception $e) {
                $this->log->error('Formula failed: ' . $e->getMessage());
            }
        }

        $customScript = $this->metadata->get(['formula', $entity->getEntityType(), 'beforeSaveCustomScript']);

        if ($customScript) {
            try {
                $this->formulaManager->run($customScript, $entity, $variables);
            }
            catch (Exception $e) {
                $this->log->error('Formula failed: ' . $e->getMessage());
            }
        }
    }
}
