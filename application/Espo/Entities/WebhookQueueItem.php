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

namespace Espo\Entities;

use Espo\Core\Field\DateTime;
use Espo\Core\ORM\Entity;
use stdClass;

class WebhookQueueItem extends Entity
{
    public const ENTITY_TYPE = 'WebhookQueueItem';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_SUCCESS = 'Success';
    public const STATUS_FAILED = 'Failed';

    public function getAttempts(): int
    {
        return $this->get('attempts') ?? 0;
    }

    public function setStatus(string $status): self
    {
        $this->set('status', $status);

        return $this;
    }

    public function setAttempts(?int $attempts): self
    {
        $this->set('attempts', $attempts);

        return $this;
    }

    public function setProcessAt(?DateTime $processAt): self
    {
        if (!$processAt) {
            $this->set('processAt', $processAt);

            return $this;
        }

        $this->set('processAt', $processAt->toString());

        return $this;
    }

    public function setProcessedAt(?DateTime $processedAt): self
    {
        if (!$processedAt) {
            $this->set('processedAt', $processedAt);

            return $this;
        }

        $this->set('processedAt', $processedAt->toString());

        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->get('targetType');
    }

    public function getTargetId(): ?string
    {
        return $this->get('targetId');
    }

    public function getData(): stdClass
    {
        return $this->get('data') ?? (object) [];
    }
}
