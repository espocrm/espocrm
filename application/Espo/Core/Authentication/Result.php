<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use StdClass;

/**
 * An authentication result.
 */
class Result
{
    const STATUS_SUCCESS = 'success';

    const STATUS_SECOND_STEP_REQUIRED = 'secondStepRequired';

    protected $status;

    protected $message = null;

    protected $token = null;

    protected $view = null;

    protected function __construct(string $status, ?StdClass $params = null)
    {
        $this->status = $status;

        if ($params) {
            $this->message = $params->message ?? null;
            $this->token = $params->token ?? null;
            $this->view = $params->view ?? null;
        }
    }

    /**
     * Create an instance for a successful login.
     */
    public static function success()
    {
        return new Result(self::STATUS_SUCCESS);
    }

    /**
     * Create an instance for a login requiring a second step. E.g. for 2FA.
     */
    public static function secondStepRequired(StdClass $params)
    {
        return new Result(self::STATUS_SECOND_STEP_REQUIRED, $params);
    }

    /**
     * Login is successful.
     */
    public function isSuccess() : bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Second step is required. E.g. for 2FA.
     */
    public function isSecondStepRequired() : bool
    {
        return $this->status === self::STATUS_SECOND_STEP_REQUIRED;
    }

    /**
     * A status.
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * A client view to redirect to for a second step.
     */
    public function getView() : ?string
    {
        return $this->view;
    }

    /**
     * A message to show to end user for a second step.
     */
    public function getMessage() : ?string
    {
        return $this->message;
    }

    /**
     * A token can be retured to a client to be used instead of password in a request for a second step.
     */
    public function getToken() : ?string
    {
        return $this->token;
    }
}
