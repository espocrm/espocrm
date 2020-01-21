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

namespace Espo\Modules\Crm\Repositories;

use Espo\ORM\Entity;

class CaseObj extends \Espo\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        parent::init();
        $this->addDependency('serviceFactory');
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);
        $this->handleAfterSaveContacts($entity, $options);
    }

    protected function handleAfterSaveContacts(Entity $entity, array $options = [])
    {
        if ($entity->isAttributeChanged('contactId')) {
            $contactId = $entity->get('contactId');
            $contactIdList = $entity->get('contactsIds') ?? [];
            $fetchedContactId = $entity->getFetched('contactId');

            if ($fetchedContactId) {
                $previousPortalUser = $this->getEntityManager()->getRepository('User')->select(['id'])->where([
                    'contactId' => $fetchedContactId,
                    'type' => 'portal',
                ])->findOne();

                if ($previousPortalUser) {
                    $this->getInjection('serviceFactory')->create('Stream')->unfollowEntity($entity, $previousPortalUser->id);
                }
            }

            if (!$contactId) {
                if ($fetchedContactId) {
                    $this->unrelate($entity, 'contacts', $fetchedContactId);
                }
                return;
            }

            $portalUser = $this->getEntityManager()->getRepository('User')->select(['id'])->where([
                'contactId' => $contactId,
                'type' => 'portal',
                'isActive' => true,
            ])->findOne();

            if ($portalUser) {
                $this->getInjection('serviceFactory')->create('Stream')->followEntity($entity, $portalUser->id, true);
            }

            if (!in_array($contactId, $contactIdList) && !$this->isRelated($entity, 'contacts', $contactId)) {
                $this->relate($entity, 'contacts', $contactId);
            }
        }
    }
}
