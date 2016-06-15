<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\SelectManagers;

class Email extends \Espo\Core\SelectManagers\Base
{
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        $result = parent::getSelectParams($params, $withAcl, $checkWherePermission);

        if (!$this->hasJoin('users', $result) && !$this->hasLeftJoin('users', $result)) {
            $this->addLeftJoin('users', $result);
            $this->setJoinCondition('users', array(
                'userId' => $this->getUser()->id
            ), $result);
        }

        $this->addUsersColumns($result);

        return $result;
    }

    protected function boolFilterOnlyMy(&$result)
    {
        $this->addJoin('users', $result);
        $result['whereClause'][] = array(
            'usersMiddle.userId' => $this->getUser()->id
        );

        $this->addUsersColumns($result);
    }

    protected function addUsersColumns(&$result)
    {
        if (!isset($result['select'])) {
            $result['additionalSelectColumns']['usersMiddle.is_read'] = 'isRead';
            $result['additionalSelectColumns']['usersMiddle.is_important'] = 'isImportant';
            $result['additionalSelectColumns']['usersMiddle.in_trash'] = 'inTrash';
        }
    }

    protected function filterInbox(&$result)
    {
        $eaList = $this->getUser()->get('emailAddresses');
        $idList = [];
        foreach ($eaList as $ea) {
            $idList[] = $ea->id;
        }
        $result['whereClause'][] = array(
            'fromEmailAddressId!=' => $idList,
            'usersMiddle.inTrash=' => false
        );
        $this->boolFilterOnlyMy($result);
    }

    protected function filterSent(&$result)
    {
        $eaList = $this->getUser()->get('emailAddresses');
        $idList = [];
        foreach ($eaList as $ea) {
            $idList[] = $ea->id;
        }
        $result['whereClause'][] = array(
            'OR' => array(
                'fromEmailAddressId=' => $idList,
                array(
                    'status' => 'Sent',
                    'createdBy' => $this->getUser()->id
                )
            ),
            'usersMiddle.inTrash=' => false
        );
    }

    protected function filterTrash(&$result)
    {
        $result['whereClause'][] = array(
            'usersMiddle.inTrash=' => true
        );
        $this->boolFilterOnlyMy($result);
    }

    protected function filterDrafts(&$result)
    {
        $result['whereClause'][] = array(
            'status' => 'Draft',
            'createdById' => $this->getUser()->id
        );
    }

    protected function filterArchived(&$result)
    {
        $result['whereClause'][] = array(
            'status' => 'Archived'
        );
    }

    protected function accessOnlyOwn(&$result)
    {
        $this->boolFilterOnlyMy($result);
    }

    protected function accessPortalOnlyOwn(&$result)
    {
        $this->boolFilterOnlyMy($result);
    }

    protected function accessOnlyTeam(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['teams', 'teamsAccess'], $result);
        $this->addLeftJoin(['users', 'usersAccess'], $result);

        $result['whereClause'][] = array(
            'OR' => array(
                'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams'),
                'usersAccess.id' => $this->getUser()->id
            )
        );
    }

    protected function accessPortalOnlyAccount(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['users', 'usersAccess'], $result);

        $d = array(
            'usersAccess.id' => $this->getUser()->id
        );

        $accountIdList = $this->getUser()->getLinkMultipleIdList('accounts');
        if (count($accountIdList)) {
            $d['accountId'] = $accountIdList;
        }

        $contactId = $this->getUser()->get('contactId');
        if ($contactId) {
            $d[] = array(
                'parentId' => $contactId,
                'parentType' => 'Contact'
            );
        }

        $result['whereClause'][] = array(
            'OR' => $d
        );
    }

    protected function accessPortalOnlyContact(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['users', 'usersAccess'], $result);

        $d = array(
            'usersAccess.id' => $this->getUser()->id
        );

        $contactId = $this->getUser()->get('contactId');
        if ($contactId) {
            $d[] = array(
                'parentId' => $contactId,
                'parentType' => 'Contact'
            );
        }

        $result['whereClause'][] = array(
            'OR' => $d
        );
    }

    protected function textFilter($textFilter, &$result)
    {
        $d = array();

        $d['name*'] = '%' . $textFilter . '%';

        if (strlen($textFilter) >= self::MIN_LENGTH_FOR_CONTENT_SEARCH) {
            $d['bodyPlain*'] = '%' . $textFilter . '%';
            $d['body*'] = '%' . $textFilter . '%';

            $emailAddressId = $this->getEmailAddressIdByValue($textFilter);
            if ($emailAddressId) {
                $this->leftJoinEmailAddress($result);
                $d['fromEmailAddressId'] = $emailAddressId;
                $d['emailEmailAddress.emailAddressId'] = $emailAddressId;
            }
        }

        $result['whereClause'][] = array(
            'OR' => $d
        );

    }

    protected function getEmailAddressIdByValue($value)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where(array(
            'lower' => strtolower($value)
        ))->findOne();

        $emailAddressId = null;
        if ($emailAddress) {
            $emailAddressId = $emailAddress->id;
        }

        return $emailAddressId;
    }

    protected function leftJoinEmailAddress(&$result)
    {
        if (empty($result['customJoin'])) {
            $result['customJoin'] = '';
        }
        if (stripos($result['customJoin'], 'emailEmailAddress') === false) {
            $result['customJoin'] .= "
                LEFT JOIN email_email_address AS `emailEmailAddress`
                    ON
                    emailEmailAddress.email_id = email.id AND
                    emailEmailAddress.deleted = 0
            ";
        }
    }


    public function whereEmailAddress($value, &$result)
    {
        $d = array();

        $emailAddressId = $this->getEmailAddressIdByValue($value);

        if ($emailAddressId) {
            $this->leftJoinEmailAddress($result);

            $d['fromEmailAddressId'] = $emailAddressId;
            $d['emailEmailAddress.emailAddressId'] = $emailAddressId;
            $result['whereClause'][] = array(
                'OR' => $d
            );
        } else {
            if (empty($result['customWhere'])) {
                $result['customWhere'] = '';
            }
            $result['customWhere'] .= ' AND 0';
        }

    }

    protected function getWherePartIsNotRepliedIsTrue()
    {
        return array(
            'isReplied' => false
        );
    }

    protected function getWherePartIsNotRepliedIsFalse()
    {
        return array(
            'isReplied' => true
        );
    }

    protected function getWherePartIsNotReadIsTrue()
    {
        return array(
            'usersMiddle.isRead' => false
        );
    }

    protected function getWherePartIsNotReadIsFalse()
    {
        return array(
            'usersMiddle.isRead' => true
        );
    }

    protected function getWherePartIsReadIsTrue()
    {
        return array(
            'usersMiddle.isRead' => true
        );
    }

    protected function getWherePartIsReadIsFalse()
    {
        return array(
            'usersMiddle.isRead' => false
        );
    }

    protected function getWherePartIsImportantIsTrue()
    {
        return array(
            'usersMiddle.isImportant' => true
        );
    }

    protected function getWherePartIsImportantIsFalse()
    {
        return array(
            'usersMiddle.isImportant' => false
        );
    }
}

