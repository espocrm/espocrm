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

namespace tests\unit\Espo\Core\Record;

use Espo\Core\{
    Acl,
    Binding\BindingContainer,
    Binding\BindingContainerBuilder,
    Record\HookManager,
    Record\Hook\Provider,
    Record\Hook\Type,
    Record\CreateParams,
    Record\ReadParams,
    Record\UpdateParams,
    Record\DeleteParams,
    InjectableFactory,
    Utils\Metadata};

use Espo\Entities\User;
use Espo\ORM\Entity;

use tests\unit\testClasses\Core\Record\Hooks\{
    BeforeReadHook,
    BeforeCreateHook,
    BeforeUpdateHook,
    BeforeDeleteHook,
    BeforeLinkHook,
    BeforeUnlinkHook,
};

class HookManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InjectableFactory
     */
    private $injectableFactory;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var HookManager
     */
    private $manager;

    /**
     * @var Entity
     */
    private $entity;

    private $entityType = 'Test';

    private ?BindingContainer $bindingContainer;

    protected function setUp(): void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);

        $acl = $this->createMock(Acl::class);
        $user = $this->createMock(User::class);

        $this->bindingContainer = BindingContainerBuilder::create()
            ->bindInstance(User::class, $user)
            ->bindInstance(Acl::class, $acl)
            ->build();

        $provider = new Provider($this->metadata, $this->injectableFactory, $acl, $user);

        $this->manager = new HookManager($provider);

        $this->entity = $this->createEntity($this->entityType);
    }

    public function testBeforeRead(): void
    {
        $hook = $this->createMock(BeforeReadHook::class);

        $this->initHooks(Type::BEFORE_READ, [BeforeReadHook::class], [$hook]);

        $params = ReadParams::create();

        $hook
            ->expects($this->once())
            ->method('process')
            ->with($this->entity, $params);

        $this->manager->processBeforeRead($this->entity, $params);
    }

    public function testBeforeCreate(): void
    {
        $hook = $this->createMock(BeforeCreateHook::class);

        $this->initHooks(Type::BEFORE_CREATE, [BeforeCreateHook::class], [$hook]);

        $params = CreateParams::create();

        $hook
            ->expects($this->once())
            ->method('process')
            ->with($this->entity, $params);

        $this->manager->processBeforeCreate($this->entity, $params);
    }

    public function testBeforeUpdate(): void
    {
        $hook = $this->createMock(BeforeUpdateHook::class);

        $this->initHooks(Type::BEFORE_UPDATE, [BeforeUpdateHook::class], [$hook]);

        $params = UpdateParams::create();

        $hook
            ->expects($this->once())
            ->method('process')
            ->with($this->entity, $params);

        $this->manager->processBeforeUpdate($this->entity, $params);
    }

    public function testBeforeDelete(): void
    {
        $hook = $this->createMock(BeforeDeleteHook::class);

        $this->initHooks(Type::BEFORE_DELETE, [BeforeDeleteHook::class], [$hook]);

        $params = DeleteParams::create();

        $hook
            ->expects($this->once())
            ->method('process')
            ->with($this->entity, $params);

        $this->manager->processBeforeDelete($this->entity, $params);
    }

    public function testBeforeLink(): void
    {
        $hook = $this->createMock(BeforeLinkHook::class);

        $this->initHooks(Type::BEFORE_LINK, [BeforeLinkHook::class], [$hook]);

        $link = 'test';

        $hook
            ->expects($this->once())
            ->method('process')
            ->with($this->entity, $link, $this->entity);

        $this->manager->processBeforeLink($this->entity, $link, $this->entity);
    }

    public function testBeforeUnlink(): void
    {
        $hook = $this->createMock(BeforeUnlinkHook::class);

        $this->initHooks(Type::BEFORE_UNLINK, [BeforeUnlinkHook::class], [$hook]);

        $link = 'test';

        $hook
            ->expects($this->once())
            ->method('process')
            ->with($this->entity, $link, $this->entity);

        $this->manager->processBeforeUnlink($this->entity, $link, $this->entity);
    }

    private function createEntity(string $entityType): Entity
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($entityType);

        return $entity;
    }

    private function initHooks(string $type, array $hookClassNameList, array $hookList): void
    {
        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->with(['recordDefs', $this->entityType, $type . 'HookClassNameList'])
            ->willReturn($hookClassNameList);

        foreach ($hookClassNameList as $i => $className) {
            $this->injectableFactory
                ->expects($this->any())
                ->method('createWithBinding')
                ->with($className, $this->bindingContainer)
                ->willReturn($hookList[$i]);
        }
    }
}
