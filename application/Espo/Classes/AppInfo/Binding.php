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

namespace Espo\Classes\AppInfo;

use Espo\Core\{
    Utils\Module,
    Binding\EspoBindingLoader,
    Binding\Binding as BindingItem,
    Console\Command\Params,
};

class Binding
{
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function process(Params $params): string
    {
        $result = '';

        $bindingLoader = new EspoBindingLoader($this->module);

        $data = $bindingLoader->load();

        $keyList = $data->getGlobalKeyList();

        $result .= "Global:\n\n";

        foreach ($keyList as $key) {
            $result .= $this->printItem($key, $data->getGlobal($key));
        }

        $contextList = $data->getContextList();

        foreach ($contextList as $context) {
            $result .= "Context: {$context}\n\n";

            $keyList = $data->getContextKeyList($context);

            foreach ($keyList as $key) {
                $result .= $this->printItem($key, $data->getContext($context, $key));
            }
        }

        return $result;
    }

    private function printItem(string $key, BindingItem $binding): string
    {
        $result = '';

        $tab = '  ';

        $result .= $tab . "Key:   {$key}\n";

        $type = $binding->getType();
        $value = $binding->getValue();

        $typeString = [
            BindingItem::IMPLEMENTATION_CLASS_NAME => 'Implementation',
            BindingItem::CONTAINER_SERVICE => 'Service',
            BindingItem::VALUE => 'Value',
            BindingItem::CALLBACK => 'Callback',
            BindingItem::FACTORY_CLASS_NAME => 'Factory',
        ][$type];

        $result .= $tab . "Type:  {$typeString}\n";

        if ($type == BindingItem::IMPLEMENTATION_CLASS_NAME || $type == BindingItem::CONTAINER_SERVICE) {
            $result .= $tab . "Value: {$value}\n";
        }

        if ($type == BindingItem::VALUE) {
            if (is_string($value) || is_int($value) || is_float($value)) {
                $result .= $tab . "Value: {$value}\n";
            }

            if (is_bool($value)) {
                $valueString = $value ? 'true' : 'false';

                $result .= $tab . "Value: {$valueString}\n";
            }
        }

        $result .= "\n";

        return $result;
    }
}
