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

namespace Espo\Services;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\ORM\{
    Entity,
    Collection,
};

use Espo\Core\{
    Exceptions\ForbiddenSilent,
    Acl\Table as AclTable,
    Utils\Util,
    Record\Service as RecordService,
};

use Espo\Core\Di;

use Espo\Tools\{
    Export\Export as ExportTool,
    Export\Params as ExportParams,
};

use stdClass;

/**
 * @template TEntity of Entity
 * @extends RecordService<TEntity>
 */
class Record extends RecordService implements

    Di\AclManagerAware,
    Di\FileManagerAware,
    Di\SelectManagerFactoryAware,
    Di\InjectableFactoryAware,
    Di\SelectBuilderFactoryAware,
    Di\LogAware,
    \Espo\Core\Interfaces\Injectable
{
    use Di\AclManagerSetter;
    use Di\FileManagerSetter;
    use Di\SelectManagerFactorySetter;
    use Di\InjectableFactorySetter;
    use Di\SelectBuilderFactorySetter;
    use Di\LogSetter;

    /** for backward compatibility, to be removed */
    use \Espo\Core\Traits\Injectable;

    /** for backward compatibility, to be removed */
    protected $dependencyList = []; /** @phpstan-ignore-line */

    public function __construct(string $entityType = '')
    {
        parent::__construct($entityType);

        if (!$this->entityType) {
            // Detecting the entity type by the class-name.
            $name = get_class($this);

            $matches = null;

            if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
                $name = $matches[1];
            }

            $this->entityType = Util::normilizeScopeName($name);
        }

        // to be removed
        $this->init();
    }

    /**
     * @deprecated For backward compatibility, to be removed.
     * @return void
     */
    protected function init() {}

    /**
     * @deprecated For backward compatibility, a dummy method.
     */
    public function setEntityType(string $entityType): void {}

    /**
     * @deprecated Use `$this->entityType`.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @deprecated Use `$this->config`.
     *
     * @return \Espo\Core\Utils\Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @deprecated Use `$this->serviceFactory`.
     *
     * @return \Espo\Core\ServiceFactory
     */
    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    /**
     * @deprecated Since v7.0.
     * @return \Espo\Core\Select\SelectManagerFactory
     */
    protected function getSelectManagerFactory()
    {
        return $this->selectManagerFactory;
    }

    /**
     * @deprecated Use `$this->acl`.
     *
     * @return \Espo\Core\Acl
     */
    protected function getAcl()
    {
        return $this->acl;
    }

    /**
     * @deprecated Use `$this->user`.
     *
     * @return \Espo\Entities\User
     */
    protected function getUser()
    {
        return $this->user;
    }

    /**
     * @deprecated Use `$this->aclManager`.
     * @return \Espo\Core\AclManager
     */
    protected function getAclManager()
    {
        return $this->aclManager;
    }

    /**
     * @deprecated Use `$this->fileManager`.
     *
     * @return \Espo\Core\Utils\File\Manager
     */
    protected function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @deprecated Use `$this->metadata`.
     *
     * @return \Espo\Core\Utils\Metadata
     */
    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @deprecated Use `$this->fieldUtil`.
     * @return \Espo\Core\Utils\FieldUtil
     */
    protected function getFieldManagerUtil()
    {
        return $this->fieldUtil;
    }

    /**
     * @deprecated Use `$this->entityManager`.
     *
     * @return \Espo\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @deprecated
     * @param ?string $entityType
     * @return \Espo\Core\Select\SelectManager
     */
    protected function getSelectManager($entityType = null)
    {
        if (!$entityType) {
            $entityType = $this->entityType;
        }

        return $this->getSelectManagerFactory()->create($entityType);
    }

    /**
     * @deprecated
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    protected function getSelectParams($params)
    {
        $selectManager = $this->getSelectManager($this->entityType);

        $selectParams = $selectManager->getSelectParams($params, true, true, true);

        if (empty($selectParams['orderBy'])) {
            $selectManager->applyDefaultOrder($selectParams);
        }

        return $selectParams;
    }

    /**
     * @deprecated Use `$this->recordServiceContainer->get($name)`.
     *
     * @param string $name
     * @return \Espo\Core\Record\Service<Entity>
     */
    protected function getRecordService($name)
    {
        return $this->recordServiceContainer->get($name);
    }

    /**
     * @param array<string,mixed> $params
     * @param Collection<TEntity> $collection
     * @throws ForbiddenSilent
     * @deprecated
     */
    public function exportCollection(array $params, Collection $collection): string
    {
        if ($this->acl->getPermissionLevel('exportPermission') !== AclTable::LEVEL_YES) {
            throw new ForbiddenSilent("No 'export' permission.");
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No 'read' access.");
        }

        $params['entityType'] = $this->entityType;

        $export = $this->injectableFactory->create(ExportTool::class);

        return $export
            ->setParams(ExportParams::fromRaw($params))
            ->setCollection($collection)
            ->run()
            ->getAttachmentId();
    }

    /**
     * @deprecated
     * @param string[] $selectAttributeList
     */
    public function loadLinkMultipleFieldsForList(Entity $entity, array $selectAttributeList): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($selectAttributeList as $attribute) {
            if (!$entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) {
                continue;
            }

            $field = $entity->getAttributeParam($attribute, 'relation');

            if (!$field) {
                continue;
            }

            if ($entity->has($attribute)) {
                continue;
            }

            $entity->loadLinkMultipleField($field);
        }
    }

    /**
     * @deprecated Use `Espo\Core\FieldProcessing\ListLoadProcessor`.
     * @param TEntity $entity
     * @return void
     */
    public function loadAdditionalFieldsForList(Entity $entity)
    {
        $this->loadListAdditionalFields($entity);
    }

    /**
     * @deprecated Use `Espo\Core\FieldProcessing\ListLoadProcessor`.
     * @param TEntity $entity
     * @return void
     */
    public function loadAdditionalFieldsForExport(Entity $entity)
    {
    }

    /**
     * @deprecated
     * @return string[]
     */
    protected function getConvertCurrencyFieldList()
    {
        if (isset($this->convertCurrencyFieldList)) {
            return $this->convertCurrencyFieldList;
        }

        $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList($this->entityType, 'edit');

        $list = [];

        foreach ($this->fieldUtil->getEntityTypeFieldList($this->entityType) as $field) {
            if (
                $this->metadata
                    ->get(['entityDefs', $this->entityType, 'fields', $field, 'type']) !== 'currency'
            ) {
                continue;
            }

            if (in_array($field, $forbiddenFieldList)) {
                continue;
            }

            $list[] = $field;
        }

        return $list;
    }

    /**
     * @deprecated Use `Espo\Core\Currency\Converter`.
     * @param ?string[] $fieldList
     * @return stdClass
     */
    public function getConvertCurrencyValues(
        Entity $entity,
        string $targetCurrency,
        string $baseCurrency,
        stdClass $rates,
        bool $allFields = false,
        ?array $fieldList = null
    ) {
        $fieldList = $fieldList ?? $this->getConvertCurrencyFieldList();

        $data = (object) [];

        foreach ($fieldList as $field) {
            $currencyAttribute = $field . 'Currency';

            $currentCurrency = $entity->get($currencyAttribute);
            $value = $entity->get($field);

            if ($value === null) {
                continue;
            }

            if ($currentCurrency === $targetCurrency) {
                continue;
            }

            if ($currentCurrency !== $baseCurrency && !property_exists($rates, $currentCurrency)) {
                continue;
            }

            $rate1 = property_exists($rates, $currentCurrency) ? $rates->$currentCurrency : 1.0;
            $value = $value * $rate1;

            $rate2 = property_exists($rates, $targetCurrency) ? $rates->$targetCurrency : 1.0;
            $value = $value / $rate2;

            if (!$rate2) {
                continue;
            }

            $value = round($value, 2);

            $data->$currencyAttribute = $targetCurrency;

            $data->$field = $value;
        }

        return $data;
    }
}
