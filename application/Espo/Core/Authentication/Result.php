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

namespace Espo\Core\Authentication;

use Espo\Core\Authentication\Result\Data;

use Espo\Entities\User;

use stdClass;

/**
 * An authentication result.
 */
class Result
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_SECOND_STEP_REQUIRED = 'secondStepRequired';

    public const STATUS_FAIL = 'fail';

    private $user;

    private $status;

    private $message = null;

    private $token = null;

    private $view = null;

    private $loggedUser = null;

    private $failReason = null;

    private $data = null;

    private function __construct(string $status, ?User $user = null, ?Data $data = null)
    {
        $this->user = $user;
        $this->status = $status;

        $this->data = $data;

        if ($data) {
            $this->message = $data->getMessage();
            $this->token = $data->getToken();
            $this->view = $data->getView();
            $this->loggedUser = $data->getLoggedUser();
            $this->failReason = $data->getFailReason();
        }
    }

    /**
     * Create an instance for a successful login.
     */
    public static function success(User $user): self
    {
        return new self(self::STATUS_SUCCESS, $user);
    }

    /**
     * Create an instance for a failed login.
     */
    public static function fail(?string $reason = null): self
    {
        $data = $reason ?
            Data::createWithFailReason($reason) :
            Data::create();

        return new self(self::STATUS_FAIL, null, $data);
    }

    /**
     * Create an instance for a login requiring a second step. E.g. for 2FA.
     */
    public static function secondStepRequired(User $user, Data $data): self
    {
        return new self(self::STATUS_SECOND_STEP_REQUIRED, $user, $data);
    }

    /**
     * Login is successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Second step is required. E.g. for 2FA.
     */
    public function isSecondStepRequired(): bool
    {
        return $this->status === self::STATUS_SECOND_STEP_REQUIRED;
    }

    /**
     * Login is failed.
     */
    public function isFail(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    /**
     * Get a user.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Get a logged user. Considered that an admin user can log in as another user.
     * The logged user will be an admin user.
     */
    public function getLoggedUser(): ?User
    {
        return $this->loggedUser ?? $this->user;
    }

    /**
     * A status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * A client view to redirect to for a second step.
     */
    public function getView(): ?string
    {
        return $this->view;
    }

    /**
     * A message to show to a user for a second step.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * A token can be returned to a client to be used instead of password in a request for a second step.
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Additional data that can be needed for a second step.
     */
    public function getData(): ?stdClass
    {
        return $this->data ? $this->data->getData() : null;
    }

    /**
     * A fail reason.
     */
    public function getFailReason(): ?string
    {
        return $this->failReason;
    }
}
