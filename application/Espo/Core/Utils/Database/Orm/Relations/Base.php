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

namespace Espo\Core\Utils\Database\Orm\Relations;

use Espo\Core\Utils\Util;

class Base extends \Espo\Core\Utils\Database\Orm\Base
{
    /**
     * @var array<string,mixed>
     */
    private $params;

    /**
     * @var array<string,mixed>
     */
    private $foreignParams;

    /**
     * @var ?string
     */
    protected $foreignLinkName = null;

    /**
     * @var ?string
     */
    protected $foreignEntityName = null;

    /**
     * @var string[]
     */
    protected $allowedParams = [
        'relationName',
        'conditions',
        'additionalColumns',
        'midKeys',
        'noJoin',
        'indexes'
    ];

    /**
     * @return array<string,mixed>
     */
    protected function getParams()
    {
        return $this->params;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getForeignParams()
    {
        return $this->foreignParams;
    }

    /**
     * @param array<string,mixed> $params
     * @return void
     */
    protected function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @param array<string,mixed> $foreignParams
     * @return void
     */
    protected function setForeignParams(array $foreignParams)
    {
        $this->foreignParams = $foreignParams;
    }

    /**
     * @param string $foreignLinkName
     * @return void
     */
    protected function setForeignLinkName($foreignLinkName)
    {
        $this->foreignLinkName = $foreignLinkName;
    }

    /**
     * @return ?string
     */
    protected function getForeignLinkName()
    {
        return $this->foreignLinkName;
    }

    /**
     * @param string $foreignEntityName
     * @return void
     */
    protected function setForeignEntityName($foreignEntityName)
    {
        $this->foreignEntityName = $foreignEntityName;
    }

    /**
     * @return ?string
     */
    protected function getForeignEntityName()
    {
        return $this->foreignEntityName;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getForeignLinkParams()
    {
        $foreignLinkName = $this->getForeignLinkName();
        $foreignEntityName = $this->getForeignEntityName();
        $foreignLinkParams = $this->getLinkParams($foreignLinkName, $foreignEntityName);

        return $foreignLinkParams;
    }

    /**
     *
     * @param string $linkName
     * @param string $entityName
     * @param ?string $foreignLinkName
     * @param ?string $foreignEntityName
     * @return array<string,mixed>
     */
    public function process($linkName, $entityName, $foreignLinkName, $foreignEntityName)
    {
        $inputs = [
            'itemName' => $linkName,
            'entityName' => $entityName,
            'foreignLinkName' => $foreignLinkName,
            'foreignEntityName' => $foreignEntityName,
        ];

        $this->setMethods($inputs);

        $convertedDefs = $this->load($linkName, $entityName);

        $convertedDefs = $this->mergeAllowedParams($convertedDefs);

        $inputs = $this->setArrayValue(null, $inputs);

        $this->setMethods($inputs);

        return $convertedDefs;
    }

    /**
     * @param array<string,mixed> $loads
     * @return array<string,mixed>'
     */
    private function mergeAllowedParams($loads)
    {
        $linkName = $this->getLinkName();
        $entityName = $this->getEntityName();

        if (!empty($this->allowedParams)) {
            $linkParams = &$loads[$entityName]['relations'][$linkName];

            foreach ($this->allowedParams as $name) {
                $additionalParam = $this->getAllowedAdditionalParam($name);

                if (isset($additionalParam)) {
                    $linkParams[$name] = $additionalParam;

                    if (isset($linkParams[$name]) && is_array($linkParams[$name])) {
                        $linkParams[$name] = Util::merge($linkParams[$name], $additionalParam);
                    }
                }
            }
        }

        return $loads;
    }

    /**
     * @param string $allowedItemName
     * @return ?array<string,mixed>'
     */
    private function getAllowedAdditionalParam($allowedItemName)
    {
        $linkParams = $this->getLinkParams();
        $foreignLinkParams = $this->getForeignLinkParams();

        $itemLinkParams = isset($linkParams[$allowedItemName]) ? $linkParams[$allowedItemName] : null;
        $itemForeignLinkParams = isset($foreignLinkParams[$allowedItemName]) ?
            $foreignLinkParams[$allowedItemName] :
            null;

        $additionalParam = null;

        $linkName = $this->getLinkName();
        $entityName = $this->getEntityName();

        if (isset($itemLinkParams) && isset($itemForeignLinkParams)) {
            if (!empty($itemLinkParams) && !is_array($itemLinkParams)) {
                $additionalParam = $itemLinkParams;
            } else if (!empty($itemForeignLinkParams) && !is_array($itemForeignLinkParams)) {
                $additionalParam = $itemForeignLinkParams;
            } else {
                $additionalParam = Util::merge($itemLinkParams, $itemForeignLinkParams);
            }
        } else if (isset($itemLinkParams)) {
            $additionalParam = $itemLinkParams;
        } else if (isset($itemForeignLinkParams)) {
            $additionalParam = $itemForeignLinkParams;
        }

        return $additionalParam;
    }

    /**
     * @param string $linkName
     * @param string $entityType
     * @return array<string,mixed>
     */
    protected function load($linkName, $entityType)
    {
        return [];
    }
}
