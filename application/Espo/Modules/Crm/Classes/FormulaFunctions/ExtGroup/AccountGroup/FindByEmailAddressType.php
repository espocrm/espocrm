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

namespace Espo\Modules\Crm\Classes\FormulaFunctions\ExtGroup\AccountGroup;

use Espo\Core\Utils\Json;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Functions\BaseFunction;

use Espo\Core\Di;

class FindByEmailAddressType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\FileManagerAware
{
    use Di\EntityManagerSetter;
    use Di\FileManagerSetter;

    /** @var string[]  */
    private array $domainFileList = [
        'application/Espo/Modules/Crm/Resources/data/freeEmailProviderDomains.json',
        'custom/Espo/Custom/Resources/data/freeEmailProviderDomains.json',
    ];

    public function process(ArgumentList $args)
    {
        $args = $this->evaluate($args);

        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $emailAddress = $args[0];

        if (!$emailAddress) {
            return null;
        }

        if (!is_string($emailAddress)) {
            $this->log("Formula: ext\\account\\findByEmailAddress: Bad argument type.");

            return null;
        }

        $domain = $emailAddress;

        if (str_contains($emailAddress, '@')) {
            [, $domain] = explode('@', $emailAddress);
        }

        $domain = strtolower($domain);

        $em = $this->entityManager;

        $account = $em->getRDBRepository(Account::ENTITY_TYPE)
            ->where(['emailAddress' => $emailAddress])
            ->findOne();

        if ($account) {
            return $account->getId();
        }

        $ignoreList = [];

        foreach ($this->domainFileList as $file) {
            if (!$this->fileManager->isFile($file)) {
                continue;
            }

            $ignoreList = array_merge(
                $ignoreList,
                Json::decode($this->fileManager->getContents($file))
            );
        }

        $contact = $em->getRDBRepository(Contact::ENTITY_TYPE)
            ->where(['emailAddress' => $emailAddress])
            ->findOne();

        if ($contact) {
            if (!in_array($domain, $ignoreList)) {
                $account = $em->getRDBRepository(Account::ENTITY_TYPE)
                    ->join('contacts')
                    ->where([
                        'emailAddress*' => '%@' . $domain,
                        'contacts.id' => $contact->getId(),
                    ])
                    ->findOne();

                if ($account) {
                    return $account->getId();
                }
            } else {
                if ($contact->get('accountId')) {
                    return $contact->get('accountId');
                }
            }
        }

        if (in_array($domain, $ignoreList)) {
            return null;
        }

        $account = $em->getRDBRepository(Account::ENTITY_TYPE)
            ->where(['emailAddress*' => '%@' . $domain])
            ->findOne();

        if (!$account) {
            return null;
        }

        return $account->getId();
    }
}
