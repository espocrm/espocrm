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

use Espo\Entities\User;

class ResultData
{
    private $message = null;

    private $token = null;

    private $view = null;

    private $loggedUser = null;

    private $failReason = null;

    private function __construct(
        ?string $message = null,
        ?string $failReason = null,
        ?string $token = null,
        ?string $view = null,
        ?User $loggedUser = null
    ) {
        $this->message = $message;
        $this->failReason = $failReason;
        $this->token = $token;
        $this->view = $view;
        $this->loggedUser = $loggedUser;
    }

    public static function fromNothing() : self
    {
        return new self();
    }

    public static function fromFailReason(string $failReason) : self
    {
        return new self(null, $failReason);
    }

    public static function fromMessage(string $message) : self
    {
        return new self($message);
    }

    public static function fromArray(array $data) : self
    {
        return new self(
            $data['message'] ?? null,
            $data['failReason'] ?? null,
            $data['token'] ?? null,
            $data['view'] ?? null,
            $data['loggedUser'] ?? null
        );
    }

    public function getLoggedUser() : ?User
    {
        return $this->loggedUser;
    }

    public function getStatus() : string
    {
        return $this->status;
    }

    public function getView() : ?string
    {
        return $this->view;
    }

    public function getMessage() : ?string
    {
        return $this->message;
    }

    public function getToken() : ?string
    {
        return $this->token;
    }

    public function getFailReason() : ?string
    {
        return $this->failReason;
    }
}
