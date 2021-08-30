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

namespace Espo\Core\Formula\Functions\ExtGroup\PdfGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
    Exceptions\Error,
};

use Espo\Services\Pdf as Service;

use Espo\Core\Utils\Util;

use Espo\Tools\Pdf\Params;

use Espo\Core\Di;

use Exception;

class GenerateType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\ServiceFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\ServiceFactorySetter;

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
            $entity = $em->getEntity($entityType, $id);
        }
        catch (Exception $e) {
            $this->log("Message: " . $e->getMessage() . ".");

            throw new Error();
        }

        if (!$entity) {
            $this->log("Record {$entityType} {$id} does not exist.");

            throw new Error();
        }

        $template = $em->getEntity('Template', $templateId);

        if (!$template) {
            $this->log("Template {$templateId} does not exist.");

            throw new Error();
        }

        if ($fileName) {
            if (substr($fileName, -4) !== '.pdf') {
                $fileName .= '.pdf';
            }
        } else {
            $fileName = Util::sanitizeFileName($entity->get('name')) . '.pdf';
        }

        $params = Params::create()->withAcl(false);

        try {
            /** @var Service $service */
            $service = $this->serviceFactory->create('Pdf');

            $contents = $service->generate($entity, $template, $params);
        }
        catch (Exception $e) {
            $this->log("Error while generating. Message: " . $e->getMessage() . ".", 'error');

            throw new Error();
        }

        $attachment = $em->createEntity('Attachment', [
            'name' => $fileName,
            'type' => 'application/pdf',
            'contents' => $contents,
            'relatedId' => $id,
            'relatedType' => $entityType,
            'role' => 'Attachment',
        ]);

        return $attachment->getId();
    }
}
