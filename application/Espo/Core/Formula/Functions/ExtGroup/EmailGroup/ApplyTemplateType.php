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

namespace Espo\Core\Formula\Functions\ExtGroup\EmailGroup;

use Espo\Core\Exceptions\Error;

class ApplyTemplateType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('serviceFactory');
    }

    public function process(\StdClass $item)
    {
        $args = $this->fetchArguments($item);

        if (count($args) < 2) throw new Error("Formula ext\email\applyTemplate: Too few arguments.");

        $id = $args[0];
        $templateId = $args[1];
        $parentType = $args[2] ?? null;
        $parentId = $args[3] ?? null;

        if (!$id || !is_string($id))
            throw new Error("Formula ext\\email\applyTemplate: 1st argument should be string and not be empty.");

        if (!$templateId || !is_string($templateId))
            throw new Error("Formula ext\\email\applyTemplate: 2nd argument should be string and not be empty.");

        if ($parentType && !is_string($parentType))
            throw new Error("Formula ext\\email\applyTemplate: 3st argument should be string.");

        if ($parentId && !is_string($parentId))
            throw new Error("Formula ext\\email\applyTemplate: 4th argument should be string.");

        $em = $this->getInjection('entityManager');

        $email = $em->getEntity('Email', $id);
        $emailTemplate = $em->getEntity('EmailTemplate', $templateId);

        if (!$email) {
            $GLOBALS['log']->warning("Formula ext\\email\applyTemplate: Email {$id} does not exist.");
            return false;
        }
        if (!$emailTemplate) {
            $GLOBALS['log']->warning("Formula ext\\email\applyTemplate: EmailTemplate {$templateId} does not exist.");
            return false;
        }

        $status = $email->get('status');

        if ($status && in_array($status, ['Sent'])) {
            $GLOBALS['log']->warning("Formula ext\\email\applyTemplate: Can't apply template to email with 'Sent' status.");
            return false;
        }

        $emailTemplateService = $this->getInjection('serviceFactory')->create('EmailTemplate');

        $params = [];

        if (!$parentType || !$parentId) {
            $parentType = $email->get('parentType');
            $parentId = $email->get('parentId');
        }

        if ($parentType && $parentId) {
            $params['parentType'] = $parentType;
            $params['parentId'] = $parentId;
        }

        $emailAddressList = $email->get('toEmailAddresses');
        if (count($emailAddressList)) {
            $params['emailAddress'] = $emailAddressList[0]->get('name');
        }

        $data = $emailTemplateService->parseTemplate($emailTemplate, $params, true, true);

        $attachmentsIds = $email->getLinkMultipleIdList('attachments');
        $attachmentsIds = array_merge($attachmentsIds, $data['attachmentsIds']);

        $email->set([
            'name' => $data['subject'],
            'body' => $data['body'],
            'isHtml' => $data['isHtml'],
            'attachmentsIds' => $attachmentsIds,
        ]);

        $em->saveEntity($email, [
            'modifiedById' => 'system',
        ]);

        return true;
    }
}
