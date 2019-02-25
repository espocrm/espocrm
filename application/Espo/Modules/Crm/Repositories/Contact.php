<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Repositories;

use Espo\ORM\Entity;

class Contact extends \Espo\Core\ORM\Repositories\RDB
{
    public function afterSave(Entity $entity, array $options = array())
    {
        $result = parent::afterSave($entity, $options);
        $this->handleAfterSaveAccounts($entity, $options);

        if ($entity->has('targetListId')) {
            $this->relate($entity, 'targetLists', $entity->get('targetListId'));
        }

        return $result;
    }

    protected function handleAfterSaveAccounts(Entity $entity, array $options = array())
    {
        $accountIdChanged = $entity->has('accountId') && $entity->get('accountId') != $entity->getFetched('accountId');
        $titleChanged = $entity->has('title') && $entity->get('title') != $entity->getFetched('title');

        if ($accountIdChanged) {
            $accountId = $entity->get('accountId');
            if (empty($accountId)) {
                $this->unrelate($entity, 'accounts', $entity->getFetched('accountId'));
                return;
            }
        }

        if ($titleChanged) {
            if (empty($accountId)) {
                $accountId = $entity->getFetched('accountId');
                if (empty($accountId)) {
                    return;
                }
            }
        }

        if ($accountIdChanged || $titleChanged) {
            $pdo = $this->getEntityManager()->getPDO();

            $sql = "
                SELECT id, role FROM account_contact
                WHERE
                    account_id = ".$pdo->quote($accountId)." AND
                    contact_id = ".$pdo->quote($entity->id)." AND
                    deleted = 0
            ";
            $sth = $pdo->prepare($sql);
            $sth->execute();

            if ($row = $sth->fetch()) {
                if ($titleChanged && $entity->get('title') != $row['role']) {
                    $this->updateRelation($entity, 'accounts', $accountId, array(
                        'role' => $entity->get('title')
                    ));
                }
            } else {
                if ($accountIdChanged) {
                    $this->relate($entity, 'accounts', $accountId, array(
                        'role' => $entity->get('title')
                    ));
                }
            }
        }
    }
}

