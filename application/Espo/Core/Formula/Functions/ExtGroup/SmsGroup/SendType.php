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

namespace Espo\Core\Formula\Functions\ExtGroup\SmsGroup;

use Espo\Core\Formula\Exceptions\FunctionRuntimeError;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Sms\SmsSender;
use Espo\Entities\Sms;
use Espo\Core\Di;
use Exception;

/**
 * @noinspection PhpUnused
 */
class SendType extends BaseFunction implements

    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $evaluatedArgs = $this->evaluate($args);

        $id = $evaluatedArgs[0];

        if (!$id || !is_string($id)) {
            $this->throwBadArgumentType(1, 'string');
        }


        $sms = $this->entityManager->getRDBRepositoryByClass(Sms::class)->getById($id);

        if (!$sms) {
            throw new FunctionRuntimeError("SMS $id does not exist.");
        }

        if ($sms->getStatus() === Sms::STATUS_SENT) {
            throw new FunctionRuntimeError("Can't send SMS that has 'Sent' status.");
        }

        try {
            $this->createSender()->send($sms);

            $this->entityManager->saveEntity($sms);
        } catch (Exception $e) {
            $sms->setStatus(Sms::STATUS_FAILED);
            $this->entityManager->saveEntity($sms);

            throw new FunctionRuntimeError("Error while sending SMS. {$e->getMessage()}", previous: $e);
        }

        return true;
    }

    private function createSender(): SmsSender
    {
        return $this->injectableFactory->create(SmsSender::class);
    }
}
