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

use Espo\Core\Exceptions\Forbidden;
use Espo\Tools\EmailTemplate\Processor;
use Espo\Tools\EmailTemplate\Params;
use Espo\Tools\EmailTemplate\Data;
use Espo\Entities\EmailTemplate as EmailTemplateEntity;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Di;

/**
 * @deprecated For bc. Use `Espo\Tools\EmailTemplate\Service`.
 *
 * @extends Record<\Espo\Entities\EmailTemplate>
 */
class EmailTemplate extends Record implements

    Di\FieldUtilAware
{
    use Di\FieldUtilSetter;

    /**
     * @deprecated For bc. Use `Espo\Tools\EmailTemplate\Processor`.
     *
     * @param array<string, mixed> $params
     * @return array{
     *   subject: string,
     *   body: string,
     *   isHtml: bool,
     *   attachmentsIds: string[],
     *   attachmentsNames: \stdClass,
     * }
     */
    public function parseTemplate(
        EmailTemplateEntity $emailTemplate,
        array $params = [],
        bool $copyAttachments = false,
        bool $skipAcl = false
    ): array {

        $paramsInternal = Params::create()
            ->withApplyAcl(!$skipAcl)
            ->withCopyAttachments($copyAttachments);

        $data = Data::create()
            ->withEmailAddress($params['emailAddress'] ?? null)
            ->withEntityHash($params['entityHash'] ?? [])
            ->withParent($params['parent'] ?? null)
            ->withParentId($params['parentId'] ?? null)
            ->withParentType($params['parentType'] ?? null)
            ->withRelatedId($params['relatedId'] ?? null)
            ->withRelatedType($params['relatedType'] ?? null)
            ->withUser($this->user);

        $result = $this->createProcessor()->process($emailTemplate, $paramsInternal, $data);

        /** @var array{
          *   subject: string,
          *   body: string,
          *   isHtml: bool,
          *   attachmentsIds: string[],
          *   attachmentsNames: \stdClass,
          * }
         */
        return get_object_vars($result->getValueMap());
    }

    /**
     * @deprecated For bc. Use `Espo\Tools\EmailTemplate\Service`.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws Forbidden
     * @throws NotFound
     */
    public function parse(string $id, array $params = [], bool $copyAttachments = false): array
    {
        /** @var EmailTemplateEntity|null $emailTemplate */
        $emailTemplate = $this->getEntity($id);

        if (empty($emailTemplate)) {
            throw new NotFound();
        }

        return $this->parseTemplate($emailTemplate, $params, $copyAttachments);
    }

    private function createProcessor(): Processor
    {
        return $this->injectableFactory->create(Processor::class);
    }
}
