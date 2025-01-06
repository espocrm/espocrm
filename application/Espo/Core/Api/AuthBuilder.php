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

namespace Espo\Core\Api;

use Espo\Core\Authentication\Authentication;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\ContextualBinder;
use Espo\Core\InjectableFactory;

use RuntimeException;

/**
 * Builds Auth instance.
 */
class AuthBuilder
{
    private bool $authRequired = false;
    private bool $isEntryPoint = false;
    private ?Authentication $authentication = null;

    public function __construct(private InjectableFactory $injectableFactory)
    {}

    public function setAuthentication(Authentication $authentication): self
    {
        $this->authentication = $authentication;

        return $this;
    }

    public function setAuthRequired(bool $authRequired): self
    {
        $this->authRequired = $authRequired;

        return $this;
    }

    public function forEntryPoint(): self
    {
        $this->isEntryPoint = true;

        return $this;
    }

    public function build(): Auth
    {
        if (!$this->authentication) {
            throw new RuntimeException("Authentication is not set.");
        }

        return $this->injectableFactory->createWithBinding(
            Auth::class,
            BindingContainerBuilder
                ::create()
                ->bindInstance(Authentication::class, $this->authentication)
                ->inContext(Auth::class, function (ContextualBinder $binder) {
                    $binder
                        ->bindValue('$authRequired', $this->authRequired)
                        ->bindValue('$isEntryPoint', $this->isEntryPoint);
                })
                ->build()
        );
    }
}
