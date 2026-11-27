<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Rebuild\Actions;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Rebuild\RebuildAction;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Entities\Settings;
use Espo\ORM\Defs;
use Espo\ORM\EntityManager;

/**
 * Corrects names for Link and Link-multiple fields in the config.
 * Emits warning if the record does not exist.
 *
 * Link-Parent is not supported.
 *
 * @noinspection PhpUnused
 */
class CorrectSettingsLinks implements RebuildAction
{
    public function __construct(
        private Defs $defs,
        private Config $config,
        private Config\ConfigWriter $configWriter,
        private EntityManager $entityManager,
        private Log $log,
    ) {}

    public function process(): void
    {
        $attributes = [];

        foreach ($this->defs->getEntity(Settings::ENTITY_TYPE)->getFieldList() as $field) {
            $this->processItem($field, $attributes);
        }

        if ($attributes === []) {
            return;
        }

        $this->configWriter->setMultiple($attributes);
        $this->configWriter->save();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function processItem(Defs\FieldDefs $field, array &$attributes): void
    {
        if ($field->getType() === FieldType::LINK) {
            $this->processItemLink($field, $attributes);

            return;
        }

        if ($field->getType() === FieldType::LINK_MULTIPLE) {
            $this->processItemLinkMultiple($field, $attributes);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function processItemLink(Defs\FieldDefs $field, array &$attributes): void
    {
        $fieldName = $field->getName();
        $entityType = $field->getParam(Defs\Params\RelationParam::ENTITY);

        if (!$entityType) {
            return;
        }

        $id = $this->config->get($fieldName . 'Id');

        if (!is_string($id)) {
            return;
        }

        if (!$this->entityManager->hasRepository($entityType)) {
            return;
        }

        $entity = $this->entityManager->getEntityById($entityType, $id);

        $nameValue = $entity?->get(Field::NAME);

        if (!$entity) {
            $this->logNotExists($fieldName);
        }

        $nameAttribute = $fieldName . 'Name';

        if ($nameValue === $this->config->get($nameAttribute)) {
            return;
        }

        $attributes[$nameAttribute] = $nameValue;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function processItemLinkMultiple(Defs\FieldDefs $field, array &$attributes): void
    {
        $fieldName = $field->getName();
        $entityType = $field->getParam(Defs\Params\RelationParam::ENTITY);

        if (!$entityType) {
            return;
        }

        $ids = $this->config->get($fieldName . 'Ids');

        if (!is_array($ids)) {
            return;
        }

        $ids = array_values(array_filter($ids, fn ($id) => is_string($id)));

        if (!$this->entityManager->hasRepository($entityType)) {
            return;
        }

        /** @var array<string, ?string> $foundNames */
        $foundNames = [];

        if ($ids !== []) {
            $collection = $this->entityManager
                ->getRDBRepository($entityType)
                ->sth()
                ->where([Field::ID => $ids])
                ->find();

            foreach ($collection as $entity) {
                $foundNames[$entity->getId()] = $entity->get(Field::NAME);
            }
        }

        $names = [];
        $hasNotExisting = false;

        foreach ($ids as $id) {
            if (!array_key_exists($id, $foundNames)) {
                $hasNotExisting = true;

                continue;
            }

            $names[$id] = $foundNames[$id];
        }

        if ($hasNotExisting) {
            $this->logNotExists($fieldName);
        }

        $namesAttribute = $fieldName . 'Names';

        $currentNames = $this->config->get($namesAttribute);

        if (is_object($currentNames) && (array) $currentNames === $names) {
            return;
        }

        if ($currentNames === null && $names === []) {
            return;
        }

        $attributes[$namesAttribute] = (object) $names;
    }

    private function logNotExists(string $fieldName): void
    {
        $this->log->warning("Non-existent record in the config parameter '$fieldName'.");
    }
}
