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

namespace Espo\Tools\EmailTemplate;

use Espo\Core\Name\Field;
use Espo\Entities\Attachment;

use stdClass;

class Result
{
    private $subject;
    private $body;
    private $isHtml = false;
    private $attachmentList = [];

    /**
     * @param Attachment[] $attachmentList
     */
    public function __construct(
        string $subject,
        string $body,
        bool $isHtml,
        array $attachmentList
    ) {
        $this->subject = $subject;
        $this->body = $body;
        $this->isHtml = $isHtml;
        $this->attachmentList = $attachmentList;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    /**
     * @return Attachment[]
     */
    public function getAttachmentList(): array
    {
        return $this->attachmentList;
    }

    /**
     * @return string[]
     */
    public function getAttachmentIdList(): array
    {
        $list = [];

        foreach ($this->attachmentList as $attachment) {
            $list[] = $attachment->getId();
        }

        return $list;
    }

    public function getValueMap(): stdClass
    {
        $attachmentsIds = [];
        $attachmentsNames = (object) [];

        foreach ($this->attachmentList as $attachment) {
            $id = $attachment->getId();

            $attachmentsIds[] = $id;
            $attachmentsNames->$id = $attachment->get(Field::NAME);
        }

        return (object) [
            'subject' => $this->subject,
            'body' => $this->body,
            'isHtml' => $this->isHtml,
            'attachmentsIds' => $attachmentsIds,
            'attachmentsNames' => $attachmentsNames,
        ];
    }
}
