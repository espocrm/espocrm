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

namespace Espo\Core\Authentication\Hook;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ServiceUnavailable;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Api\Request;
use Espo\Core\Authentication\Result;

class Manager
{

    public function __construct(private Metadata $metadata, private InjectableFactory $injectableFactory)
    {}

    /**
     * @throws ServiceUnavailable
     * @throws Forbidden
     */
    public function processBeforeLogin(AuthenticationData $data, Request $request): void
    {
        foreach ($this->getBeforeLoginHookList() as $hook) {
            $hook->process($data, $request);
        }
    }

    /**
     * @throws Forbidden
     */
    public function processOnLogin(Result $result, AuthenticationData $data, Request $request): void
    {
        foreach ($this->getOnLoginHookList() as $hook) {
            $hook->process($result, $data, $request);
        }
    }

    public function processOnFail(Result $result, AuthenticationData $data, Request $request): void
    {
        foreach ($this->getOnFailHookList() as $hook) {
            $hook->process($result, $data, $request);
        }
    }

    public function processOnSuccess(Result $result, AuthenticationData $data, Request $request): void
    {
        foreach ($this->getOnSuccessHookList() as $hook) {
            $hook->process($result, $data, $request);
        }
    }

    public function processOnSuccessByToken(Result $result, AuthenticationData $data, Request $request): void
    {
        foreach ($this->getOnSuccessByTokenHookList() as $hook) {
            $hook->process($result, $data, $request);
        }
    }

    public function processOnSecondStepRequired(Result $result, AuthenticationData $data, Request $request): void
    {
        foreach ($this->getOnSecondStepRequiredHookList() as $hook) {
            $hook->process($result, $data, $request);
        }
    }

    /**
     * @return class-string<BeforeLogin|OnResult>[]
     */
    private function getHookClassNameList(string $type): array
    {
        $key = $type . 'HookClassNameList';

        /** @var class-string<BeforeLogin|OnResult>[] */
        return $this->metadata->get(['app', 'authentication', $key]) ?? [];
    }

    /**
     * @return BeforeLogin[]
     */
    private function getBeforeLoginHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('beforeLogin') as $className) {
            /** @var class-string<BeforeLogin> $className */
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }

    /**
     * @return OnLogin[]
     */
    private function getOnLoginHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('onLogin') as $className) {
            /** @var class-string<OnLogin> $className */
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }

    /**
     * @return OnResult[]
     */
    private function getOnFailHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('onFail') as $className) {
            /** @var class-string<OnResult> $className */
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }

    /**
     * @return OnResult[]
     */
    private function getOnSuccessHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('onSuccess') as $className) {
            /** @var class-string<OnResult> $className */
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }

    /**
     * @return OnResult[]
     */
    private function getOnSuccessByTokenHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('onSuccessByToken') as $className) {
            /** @var class-string<OnResult> $className */
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }

    /**
     * @return OnResult[]
     */
    private function getOnSecondStepRequiredHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('onSecondStepRequired') as $className) {
            /** @var class-string<OnResult> $className */
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }
}
