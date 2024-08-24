<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Services;

use Espo\Core\Exceptions\NotFound;
use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Tools\Pdf\Data;
use Espo\Tools\Pdf\Params;
use Espo\Tools\Pdf\Service;
use Espo\Entities\Template;

/**
 * @deprecated Left for bc.
 * @todo Remove in v9.0.
 */
class Pdf
{
    private Service $service;

    public function __construct(
        Service $service
    ) {
        $this->service = $service;
    }

    /**
     * @deprecated
     * @throws Error
     * @throws Forbidden
     */
    public function generate(Entity $entity, Template $template, ?Params $params = null, ?Data $data = null): string
    {
        $additionalData = null;

        if ($data) {
            $additionalData = get_object_vars($data->getAdditionalTemplateData());
        }

        return $this->buildFromTemplate($entity, $template, false, $additionalData);
    }

    /**
     * @param ?array<string, mixed> $additionalData
     * @throws Error
     * @throws Forbidden
     * @throws NotFound
     *
     * @deprecated
     */
    public function buildFromTemplate(
        Entity $entity,
        Template $template,
        bool $displayInline = false,
        ?array $additionalData = null
    ): string {

        $data = Data::create()
            ->withAdditionalTemplateData(
                (object) ($additionalData ?? [])
            );

        $contents = $this->service->generate(
            $entity->getEntityType(),
            $entity->getId(),
            $template->getId(),
            null,
            $data
        );

        return $contents->getString();
    }
}
