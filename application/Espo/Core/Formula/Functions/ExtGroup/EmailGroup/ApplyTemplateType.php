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

namespace Espo\Core\Formula\Functions\ExtGroup\EmailGroup;

use Espo\Core\Utils\SystemUser;
use Espo\Entities\Email;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Di;
use Espo\Entities\EmailTemplate;
use Espo\Tools\EmailTemplate\Data;
use Espo\Tools\EmailTemplate\Params;
use Espo\Tools\EmailTemplate\Processor;

class ApplyTemplateType extends BaseFunction implements

    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 2) {
            $this->throwTooFewArguments(2);
        }

        $args = $this->evaluate($args);

        $id = $args[0];
        $templateId = $args[1];
        $parentType = $args[2] ?? null;
        $parentId = $args[3] ?? null;

        if (!$id || !is_string($id)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!$templateId || !is_string($templateId)) {
            $this->throwBadArgumentType(2, 'string');
        }

        if ($parentType && !is_string($parentType)) {
            $this->throwBadArgumentType(3, 'string');
        }

        if ($parentId && !is_string($parentId)) {
            $this->throwBadArgumentType(4, 'string');
        }

        $em = $this->entityManager;

        /** @var ?Email $email */
        $email = $em->getEntityById(Email::ENTITY_TYPE, $id);
        /** @var ?EmailTemplate $emailTemplate */
        $emailTemplate = $em->getEntityById(EmailTemplate::ENTITY_TYPE, $templateId);

        if (!$email) {
            $this->log("Email {$id} does not exist.");

            return false;
        }

        if (!$emailTemplate) {
            $this->log("EmailTemplate {$templateId} does not exist.");

            return false;
        }

        $status = $email->getStatus();

        if ($status && $status === Email::STATUS_SENT) {
            $this->log("Can't apply template to email with 'Sent' status.");

            return false;
        }

        $processor = $this->injectableFactory->create(Processor::class);

        $params = Params::create()
            ->withCopyAttachments(true)
            ->withApplyAcl(false);

        $data = Data::create();

        if (!$parentType || !$parentId) {
            $parentType = $email->getParentType();
            $parentId = $email->getParentId();
        }

        if ($parentType && $parentId) {
            $data = $data
                ->withParentId($parentId)
                ->withParentType($parentType);
        }

        $data = $data->withEmailAddress(
            $email->getToAddressList()[0] ?? null
        );

        $emailData = $processor->process($emailTemplate, $params, $data);

        $attachmentsIdList = $email->getLinkMultipleIdList('attachments');

        $attachmentsIdList = array_merge(
            $attachmentsIdList,
            $emailData->getAttachmentIdList()
        );

        $email
            ->setSubject($emailData->getSubject())
            ->setBody($emailData->getBody())
            ->setIsHtml($emailData->isHtml())
            ->setAttachmentIdList($attachmentsIdList);

        $systemUserId = $this->injectableFactory->create(SystemUser::class)->getId();

        $em->saveEntity($email, [
            'modifiedById' => $systemUserId,
        ]);

        return true;
    }
}
