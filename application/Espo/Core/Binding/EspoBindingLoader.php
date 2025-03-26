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

namespace Espo\Core\Binding;

use Espo\Core\Utils\Module;
use Espo\Binding;

class EspoBindingLoader implements BindingLoader
{
    /** @var string[] */
    private array $moduleNameList;

    public function __construct(Module $module)
    {
        $this->moduleNameList = $module->getOrderedList();
    }

    public function load(): BindingData
    {
        $data = new BindingData();
        $binder = new Binder($data);

        (new Binding())->process($binder);

        foreach ($this->moduleNameList as $moduleName) {
            $this->loadModule($binder, $moduleName);
        }

        $this->loadCustom($binder);

        return $data;
    }

    private function loadModule(Binder $binder, string $moduleName): void
    {
        $className = 'Espo\\Modules\\' . $moduleName . '\\Binding';

        if (!class_exists($className)) {
            return;
        }

        /** @var class-string<BindingProcessor> $className */

        (new $className())->process($binder);
    }

    private function loadCustom(Binder $binder): void
    {
        /** @var class-string<BindingProcessor>|string $className */
        $className = 'Espo\\Custom\\Binding';

        if (!class_exists($className)) {
            return;
        }

        /** @var class-string<BindingProcessor> $className */

        (new $className())->process($binder);
    }
}
