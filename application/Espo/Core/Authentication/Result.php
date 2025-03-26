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

namespace Espo\Core\Authentication;

use Espo\Core\Authentication\Result\Data;
use Espo\Entities\User;

use stdClass;

/**
 * An authentication result.
 *
 * Immutable.
 */
class Result
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_SECOND_STEP_REQUIRED = 'secondStepRequired';
    public const STATUS_FAIL = 'fail';

    private ?User $user;
    private string $status;
    private ?string $message = null;
    private ?string $token = null;
    private ?string $view = null;
    private ?string $failReason = null;
    private bool $bypassSecondStep = false;
    private ?Data $data;

    private function __construct(string $status, ?User $user = null, ?Data $data = null)
    {
        $this->user = $user;
        $this->status = $status;

        $this->data = $data;

        if ($data) {
            $this->message = $data->getMessage();
            $this->token = $data->getToken();
            $this->view = $data->getView();
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
     * The second step is required.
     */
    public function isSecondStepRequired(): bool
    {
        return $this->status === self::STATUS_SECOND_STEP_REQUIRED;
    }

    /**
     * To bypass the second step.
     *
     * @since 8.4.0
     */
    public function bypassSecondStep(): bool
    {
        return $this->bypassSecondStep;
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
     * @deprecated Use `getUser`.
     */
    public function getLoggedUser(): ?User
    {
        return $this->user;
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
        return $this->data?->getData();
    }

    /**
     * A fail reason.
     */
    public function getFailReason(): ?string
    {
        return $this->failReason;
    }

    /**
     * Clone with bypass second step.
     *
     * @since 8.4.0
     */
    public function withBypassSecondStep(bool $bypassSecondStep = true): self
    {
        $obj = clone $this;
        $obj->bypassSecondStep = $bypassSecondStep;

        return $obj;
    }
}
