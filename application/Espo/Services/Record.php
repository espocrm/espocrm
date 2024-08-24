<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Services;

use Espo\Core\Acl\Permission;
use Espo\Core\ORM\Defs\AttributeParam;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Record\Service as RecordService;
use Espo\Core\Utils\Util;
use Espo\Tools\Export\Export as ExportTool;
use Espo\Tools\Export\Params as ExportParams;
use Espo\Core\Di;

/**
 * Extending is not recommended. Use composition with metadata > recordDefs.
 *
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

            $this->entityType = Util::normalizeScopeName($name);
        }

        // to be removed
        $this->init();
    }

    /**
     * @deprecated For backward compatibility, to be removed.
     * @return void
     * @todo Remove in v9.0.
     */
    protected function init() {}

    /**
     * @deprecated For backward compatibility, a dummy method.
     */
    public function setEntityType(string $entityType): void {}

    /**
     * @deprecated Use `$this->entityType`.
     * @todo Remove in v9.0.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @deprecated Use `$this->config`.
     * @return \Espo\Core\Utils\Config
     * @todo Remove in v9.0.
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @deprecated Use `$this->serviceFactory`.
     * @return \Espo\Core\ServiceFactory
     * @todo Remove in v9.0.
     */
    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    /**
     * @deprecated Since v7.0.
     * @return \Espo\Core\Select\SelectManagerFactory
     * @todo Remove in v9.0.
     */
    protected function getSelectManagerFactory()
    {
        return $this->selectManagerFactory;
    }

    /**
     * @deprecated Use `$this->acl`.
     * @return \Espo\Core\Acl
     * @todo Remove in v9.0.
     */
    protected function getAcl()
    {
        return $this->acl;
    }

    /**
     * @deprecated Use `$this->user`.
     * @return \Espo\Entities\User
     * @todo Remove in v9.0.
     */
    protected function getUser()
    {
        return $this->user;
    }

    /**
     * @deprecated Use `$this->aclManager`.
     * @return \Espo\Core\AclManager
     * @todo Remove in v9.0.
     */
    protected function getAclManager()
    {
        return $this->aclManager;
    }

    /**
     * @deprecated Use `$this->fileManager`.
     * @return \Espo\Core\Utils\File\Manager
     * @todo Remove in v9.0.
     */
    protected function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @deprecated Use `$this->metadata`.
     * @return \Espo\Core\Utils\Metadata
     * @todo Remove in v9.0.
     */
    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @deprecated Use `$this->fieldUtil`.
     * @return \Espo\Core\Utils\FieldUtil
     * @todo Remove in v9.0.
     */
    protected function getFieldManagerUtil()
    {
        return $this->fieldUtil;
    }

    /**
     * @deprecated Use `$this->entityManager`.
     * @return \Espo\ORM\EntityManager
     * @todo Remove in v9.0.
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @deprecated
     * @todo Remove in v9.0.
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
     * @todo Remove in v9.0.
     * @param array<string, mixed> $params
     * @return array<string, mixed>
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
     * @todo Remove in v9.0.
     * @param string $name
     * @return \Espo\Core\Record\Service<Entity>
     */
    protected function getRecordService($name)
    {
        return $this->recordServiceContainer->get($name);
    }

    /**
     * @param array<string, mixed> $params
     * @param Collection<TEntity> $collection
     * @throws ForbiddenSilent
     * @deprecated
     * @todo Remove in v9.0.
     */
    public function exportCollection(array $params, Collection $collection): string
    {
        if ($this->acl->getPermissionLevel(Permission::EXPORT) !== AclTable::LEVEL_YES) {
            throw new ForbiddenSilent("No 'export' permission.");
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No 'read' access.");
        }

        $params['entityType'] = $this->entityType;

        $export = $this->injectableFactory->create(ExportTool::class);

        $exportParams = ExportParams::fromRaw($params);

        if (isset($params['params'])) {
            foreach (get_object_vars($params['params']) as $k => $v) {
                $exportParams = $exportParams->withParam($k, $v);
            }
        }

        return $export
            ->setParams($exportParams)
            ->setCollection($collection)
            ->run()
            ->getAttachmentId();
    }

    /**
     * @deprecated
     * @param string[] $selectAttributeList
     * @todo Remove in v9.0.
     */
    public function loadLinkMultipleFieldsForList(Entity $entity, array $selectAttributeList): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($selectAttributeList as $attribute) {
            if (!$entity->getAttributeParam($attribute, AttributeParam::IS_LINK_MULTIPLE_ID_LIST)) {
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
     * @todo Remove in v9.0.
     * @param TEntity $entity
     * @return void
     */
    public function loadAdditionalFieldsForList(Entity $entity)
    {
        $this->loadListAdditionalFields($entity);
    }

    /**
     * @deprecated Use `Espo\Core\FieldProcessing\ListLoadProcessor`.
     * @todo Remove in v9.0.
     * @param TEntity $entity
     * @return void
     */
    public function loadAdditionalFieldsForExport(Entity $entity)
    {}
}
