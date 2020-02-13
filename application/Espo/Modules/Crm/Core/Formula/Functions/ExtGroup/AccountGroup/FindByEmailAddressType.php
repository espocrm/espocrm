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

namespace Espo\Modules\Crm\Core\Formula\Functions\ExtGroup\AccountGroup;

use Espo\Core\Exceptions\Error;

class FindByEmailAddressType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('fileManager');
    }

    public function process(\StdClass $item)
    {
        $args = $this->fetchArguments($item);

        if (count($args) < 1) throw new Error("Formula: ext\\account\\findByEmailAddress: Not enough arguments.");

        $emailAddress = $args[0];

        if (!$emailAddress) return null;

        if (!is_string($emailAddress)) {
            $GLOBALS['log']->warning("Formula: ext\\account\\findByEmailAddress: Bad argument type.");
            return null;
        }

        $domain = $emailAddress;
        if (strpos($emailAddress, '@') !== false) {
            list($p1, $domain) = explode('@', $emailAddress);
        }

        $domain = strtolower($domain);

        $em = $this->getInjection('entityManager');

        $account = $em->getRepository('Account')->where([
            'emailAddress' => $emailAddress,
        ])->findOne();

        if ($account) return $account->id;

        $ignoreList = json_decode($this->getInjection('fileManager')->getContents(
            'application/Espo/Modules/Crm/Resources/data/freeEmailProviderDomains.json'
        )) ?? [];

        $contact = $em->getRepository('Contact')->where([
            'emailAddress' => $emailAddress,
        ])->findOne();

        if ($contact) {
            if (!in_array($domain, $ignoreList)) {
                $account = $em->getRepository('Account')->join(['contacts'])->where([
                    'emailAddress*' => '%' . $domain,
                    'contacts.id' => $contact->id,
                ])->findOne();
                if ($account) return $account->id;
            } else {
                if ($contact->get('accountId')) return $contact->get('accountId');
            }
        }

        if (in_array($domain, $ignoreList)) return null;

        $account = $em->getRepository('Account')->where([
            'emailAddress*' => '%' . $domain,
        ])->findOne();

        if (!$account) return null;

        return $account->id;
    }
}
