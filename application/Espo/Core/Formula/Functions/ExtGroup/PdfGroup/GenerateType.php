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

namespace Espo\Core\Formula\Functions\ExtGroup\PdfGroup;

use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Entities\Attachment;
use Espo\Entities\Template;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Utils\Util;
use Espo\Tools\Pdf\Params;
use Espo\Core\Di;

use Espo\Tools\Pdf\Service;
use Exception;

class GenerateType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\InjectableFactoryAware,
    Di\FileStorageManagerAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;
    use Di\FileStorageManagerSetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 3) {
            $this->throwTooFewArguments(3);
        }

        $args = $this->evaluate($args);

        $entityType = $args[0];
        $id = $args[1];
        $templateId = $args[2];
        $fileName = $args[3];

        if (!$entityType || !is_string($entityType)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!$id || !is_string($id)) {
            $this->throwBadArgumentType(2, 'string');
        }

        if (!$templateId || !is_string($templateId)) {
            $this->throwBadArgumentType(3, 'string');
        }

        if ($fileName && !is_string($fileName)) {
            $this->throwBadArgumentType(4, 'string');
        }

        $em = $this->entityManager;

        try {
            $entity = $em->getEntityById($entityType, $id);
        } catch (Exception $e) {
            $this->log("Message: " . $e->getMessage() . ".");

            throw new Error();
        }

        if (!$entity) {
            $this->log("Record {$entityType} {$id} does not exist.");

            throw new Error();
        }

        /** @var ?Template $template */
        $template = $em->getEntityById(Template::ENTITY_TYPE, $templateId);

        if (!$template) {
            $this->log("Template {$templateId} does not exist.");

            throw new Error();
        }

        if ($fileName) {
            if (!str_ends_with($fileName, '.pdf')) {
                $fileName .= '.pdf';
            }
        } else {
            $fileName = Util::sanitizeFileName($entity->get(Field::NAME)) . '.pdf';
        }

        $params = Params::create()->withAcl(false);

        try {
            $service = $this->injectableFactory->create(Service::class);

            $contents = $service->generate(
                $entity->getEntityType(),
                $entity->getId(),
                $template->getId(),
                $params
            );
        } catch (Exception $e) {
            $this->log("Error while generating. Message: " . $e->getMessage() . ".", 'error');

            throw new Error();
        }

        /** @var Attachment $attachment */
        $attachment = $em->getNewEntity(Attachment::ENTITY_TYPE);

        $attachment
            ->setName($fileName)
            ->setType('application/pdf')
            ->setSize($contents->getStream()->getSize())
            ->setRelated(LinkParent::create($entityType, $id))
            ->setRole(Attachment::ROLE_ATTACHMENT);

        $em->saveEntity($attachment);

        $this->fileStorageManager->putStream($attachment, $contents->getStream());

        return $attachment->getId();
    }
}
