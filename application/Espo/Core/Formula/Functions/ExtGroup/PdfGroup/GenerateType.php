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

namespace Espo\Core\Formula\Functions\ExtGroup\PdfGroup;

use Espo\Core\Exceptions\Error;

class GenerateType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('serviceFactory');
    }

    public function process(\StdClass $item)
    {
        $args = $this->fetchArguments($item);

        if (count($args) < 3) throw new Error("Formula ext\\pdf\\generate: Too few arguments.");

        $entityType = $args[0];
        $id = $args[1];
        $templateId = $args[2];
        $fileName = $args[3];

        if (!$entityType || !is_string($entityType))
            throw new Error("Formula ext\\pdf\\generate: 1st argument should be string and not be empty.");
        if (!$id || !is_string($id))
            throw new Error("Formula ext\\pdf\\generate: 2nd argument should be string and not be empty.");
        if (!$templateId || !is_string($templateId))
            throw new Error("Formula ext\\pdf\\generate: 3rd argument should be string and not be empty.");
        if ($fileName && !is_string($fileName))
            throw new Error("Formula ext\\pdf\\generate: 4rd argument should be string.");

        $em = $this->getInjection('entityManager');

        try {
            $entity = $em->getEntity($entityType, $id);
        } catch (\Exception $e) {
            $GLOBALS['log']->error("Formula ext\\pdf\\generate: Message: " . $e->getMessage());
            return null;
        }

        if (!$entity) {
            $GLOBALS['log']->warning("Formula ext\\pdf\\generate: Record {$entityType} {$id} does not exist.");
            return null;
        }

        $template = $em->getEntity('Template', $templateId);

        if (!$template) {
            $GLOBALS['log']->warning("Formula ext\\pdf\\generate: Template {$templateId} does not exist.");
            return null;
        }

        if ($fileName) {
            if (substr($fileName, -4) !== '.pdf') {
                $fileName .= '.pdf';
            }
        } else {
            $fileName = \Espo\Core\Utils\Util::sanitizeFileName($entity->get('name')) . '.pdf';
        }

        try {
            $contents = $this->getInjection('serviceFactory')->create('Pdf')->buildFromTemplate($entity, $template);
        } catch (\Exception $e) {
            $GLOBALS['log']->error("Formula ext\\pdf\\generate: Error while generating. Message: " . $e->getMessage());
            return null;
        }

        $attachment = $em->createEntity('Attachment', [
            'name' => $fileName,
            'type' => 'application/pdf',
            'contents' => $contents,
            'relatedId' => $id,
            'relatedType' => $entityType,
            'role' => 'Attachment',
        ]);

        return $attachment->id;
    }
}
