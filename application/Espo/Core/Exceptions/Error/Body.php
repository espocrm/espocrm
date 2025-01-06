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

namespace Espo\Core\Exceptions\Error;

use Espo\Core\Utils\Json;

/**
 * A wrapper for error message data for the frontend. Supposed to be passed encoded to `createWithBody`
 * methods of exceptions.
 */
class Body
{
    private ?string $messageTranslationLabel = null;
    private ?string $messageTranslationScope = null;
    /** @var ?array<string, string> */
    private ?array $messageTranslationData = null;
    private ?string $message = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * A translatable message to display in frontend. Labels should be in the `messages` category.
     *
     * @param ?array<string, string> $data
     */
    public function withMessageTranslation(string $label, ?string $scope = null, ?array $data = null): self
    {
        $obj = clone $this;

        $obj->messageTranslationLabel = $label;
        $obj->messageTranslationScope = $scope;
        $obj->messageTranslationData = $data;

        return $obj;
    }

    public function withMessage(string $message): self
    {
        $obj = clone $this;
        $obj->message = $message;

        return $obj;
    }

    public function encode(): string
    {
        $data = (object) [];

        if ($this->messageTranslationLabel) {
            $messageTranslationData = (object) ($this->messageTranslationData ?? []);

            $data->messageTranslation = (object) [
                'label' => $this->messageTranslationLabel,
                'scope' => $this->messageTranslationScope,
                'data' => $messageTranslationData,
            ];
        }

        if ($this->message) {
            $data->message = $this->message;
        }

        return Json::encode($data);
    }
}
