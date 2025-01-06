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

namespace Espo\Core\Upgrades\Migration;

use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use RuntimeException;

class AfterUpgradeRunner
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private DataManager $dataManager
    ) {}

    public function run(string $step): void
    {
        $dir = 'V' . str_replace('.', '_', $step);

        $className = "Espo\\Core\\Upgrades\\Migrations\\$dir\\AfterUpgrade";

        if (!class_exists($className)) {
            throw new RuntimeException("No after-upgrade script $step.");
        }

        try {
            $this->dataManager->rebuild();
        } catch (Error $e) {
            throw new RuntimeException("Error while rebuild: " . $e->getMessage());
        }

        /** @var Script $script */
        $script = $this->injectableFactory->createWith($className, ['isUpgrade' => false]);
        $script->run();

        try {
            $this->dataManager->rebuild();
        } catch (Error $e) {
            throw new RuntimeException("Error while rebuild: " . $e->getMessage());
        }
    }
}
