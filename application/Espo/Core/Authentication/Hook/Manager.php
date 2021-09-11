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

namespace Espo\Core\Authentication\Hook;

use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;

use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Api\Request;
use Espo\Core\Authentication\Result;

class Manager
{
    private $metadata;

    private $injectableFactory;

    public function __construct(Metadata $metadata, InjectableFactory $injectableFactory)
    {
        $this->metadata = $metadata;
        $this->injectableFactory = $injectableFactory;
    }

    public function processBeforeLogin(AuthenticationData $data, Request $request): void
    {
        foreach ($this->getBeforeLoginHookList() as $hook) {
            $hook->process($data, $request);
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
     * @return string[]
     */
    private function getHookClassNameList(string $type): array
    {
        $key = $type . 'HookClassNameList';

        return $this->metadata->get(['app', 'authentication', $key]) ?? [];
    }

    /**
     * @return BeforeLogin[]
     */
    private function getBeforeLoginHookList(): array
    {
        $list = [];

        foreach ($this->getHookClassNameList('beforeLogin') as $className) {
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
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }
}
