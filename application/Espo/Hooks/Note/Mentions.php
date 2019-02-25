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

namespace Espo\Hooks\Note;

use Espo\ORM\Entity;

class Mentions extends \Espo\Core\Hooks\Base
{
    public static $order = 9;

    protected $notificationService = null;

    protected function init()
    {
        $this->addDependency('serviceFactory');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function addMentionData($entity)
    {
        $post = $entity->get('post');

        $mentionData = new \stdClass();

        $previousMentionList = array();
        if (!$entity->isNew()) {
            $data = $entity->get('data');
            if (!empty($data) && !empty($data->mentions)) {
                $previousMentionList = array_keys(get_object_vars($data->mentions));
            }
        }

        preg_match_all('/(@[\w@.-]+)/', $post, $matches);

        $mentionCount = 0;

        if (is_array($matches) && !empty($matches[0]) && is_array($matches[0])) {
            $parent = null;
            if ($entity->get('parentId') && $entity->get('parentType')) {
                $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
            }
            foreach ($matches[0] as $item) {
                $userName = substr($item, 1);
                $user = $this->getEntityManager()->getRepository('User')->where(array('userName' => $userName))->findOne();
                if ($user) {
                    if (!$this->getAcl()->checkUser('assignmentPermission', $user)) {
                        continue;
                    }
                    $m = array(
                        'id' => $user->id,
                        'name' => $user->get('name'),
                        'userName' => $user->get('userName'),
                        '_scope' => $user->getEntityName()
                    );
                    $mentionData->$item = (object) $m;
                    $mentionCount++;
                    if (!in_array($item, $previousMentionList)) {
                        if ($user->id == $this->getUser()->id) {
                            continue;
                        }
                        $this->notifyAboutMention($entity, $user, $parent);
                        $entity->addNotifiedUserId($user->id);
                    }
                }
            }
        }

        $data = $entity->get('data');
        if (empty($data)) {
            $data = new \stdClass();
        }
        if ($mentionCount) {
            $data->mentions = $mentionData;
        } else {
            unset($data->mentions);
        }

        $entity->set('data', $data);
    }

    public function beforeSave(Entity $entity)
    {
        if ($entity->get('type') == 'Post') {
            $post = $entity->get('post');

            $this->addMentionData($entity);
        }
    }

    protected function notifyAboutMention(Entity $entity, \Espo\Entities\User $user, Entity $parent = null)
    {
        if ($user->isPortal()) return;
        if ($parent) {
            if (!$this->getAclManager()->check($user, $parent, 'stream')) return;
        }
        $this->getNotificationService()->notifyAboutMentionInPost($user->id, $entity->id);
    }

    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->getServiceFactory()->create('Notification');
        }
        return $this->notificationService;
    }
}
