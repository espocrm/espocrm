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

class SendType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('serviceFactory');
        $this->addDependency('config');
    }

    public function process(\StdClass $item)
    {
        $args = $this->fetchArguments($item);

        if (count($args) < 1) throw new Error("Formula ext\email\send: Too few arguments.");
        $id = $args[0];

        if (!$id) throw new Error("Formula ext\\email\send: First argument should not be empty.");
        if (!is_string($id)) throw new Error("Formula ext\\email\send: First argument should be a string.");

        $em = $this->getInjection('entityManager');

        $email = $em->getEntity('Email', $id);

        if (!$email) {
            $GLOBALS['log']->warning("Formula ext\\email\send: Email {$id} does not exist.");
            return false;
        }

        $status = $email->get('status');

        if ($status && in_array($status, ['Sent'])) {
            $GLOBALS['log']->warning("Formula ext\\email\send: Can't send email that has 'Sent' status.");
            return false;
        }

        $service = $this->getInjection('serviceFactory')->create('Email');
        $service->loadAdditionalFields($email);

        $toSave = false;

        if ($status !== 'Sending') {
            $email->set('status', 'Sending');
            $toSave = true;
        }

        if (!$email->get('from')) {
            $from = $this->getInjection('config')->get('outboundEmailFromAddress');
            if ($from) {
                $email->set('from', $from);
                $toSave = true;
            }
        }

        if ($toSave) {
            $em->saveEntity($email, [
                'modifiedById' => 'system',
                'silent' => true,
            ]);
        }

        try {
            $service->sendEntity($email);
        } catch (\Exception $e) {
            $GLOBALS['log']->error("Formula ext\\email\send: Error while sending. Message: " . $e->getMessage());
            return false;
        }

        return true;
    }
}
