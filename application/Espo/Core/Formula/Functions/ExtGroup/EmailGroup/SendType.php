<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Formula\Exceptions\FunctionRuntimeError;
use Espo\Core\Mail\ConfigDataProvider;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Utils\SystemUser;
use Espo\Entities\Email;
use Espo\Tools\Email\SendService;
use Espo\Core\Di;
use Exception;

/**
 * @noinspection PhpUnused
 */
class SendType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\ConfigAware,
    Di\InjectableFactoryAware,
    Di\RecordServiceContainerAware
{
    use Di\EntityManagerSetter;
    use Di\ConfigSetter;
    use Di\InjectableFactorySetter;
    use Di\RecordServiceContainerSetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $args = $this->evaluate($args);

        $id = $args[0];

        if (!$id || !is_string($id)) {
            $this->throwBadArgumentType(1, 'string');
        }

        $em = $this->entityManager;

        $email = $em->getRDBRepositoryByClass(Email::class)->getById($id);

        if (!$email) {
            throw new FunctionRuntimeError("Email $id does not exist.");
        }

        if ($email->getStatus() === Email::STATUS_SENT) {
            throw new FunctionRuntimeError("Can't send email that has 'Sent' status.");
        }

        $this->recordServiceContainer
            ->getByClass(Email::class)
            ->loadAdditionalFields($email);

        $toSave = false;

        if ($email->getStatus() !== Email::STATUS_SENDING) {
            $email->setStatus(Email::STATUS_SENDING);

            $toSave = true;
        }

        if (!$email->getFromAddress()) {
            $from = $this->injectableFactory
                ->create(ConfigDataProvider::class)
                ->getSystemOutboundAddress();

            if ($from) {
                $email->setFromAddress($from);

                $toSave = true;
            }
        }

        $systemUserId = $this->injectableFactory->create(SystemUser::class)->getId();

        if ($toSave) {
            $em->saveEntity($email, [
                SaveOption::SILENT => true,
                SaveOption::MODIFIED_BY_ID => $systemUserId,
            ]);
        }

        $sendService = $this->injectableFactory->create(SendService::class);

        try {
            $sendService->send($email);
        } catch (Exception $e) {
            $email->setStatus(Email::STATUS_FAILED);
            $em->saveEntity($email);

            throw new FunctionRuntimeError("Error while sending email. {$e->getMessage()}", previous: $e);
        }

        return true;
    }
}
