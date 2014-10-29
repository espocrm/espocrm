<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/
namespace Espo\Hooks\Note;

use Espo\Core\Hooks\Base;
use Espo\Core\ServiceFactory;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Services\Notification;

class Mentions extends
    Base
{

    public static $order = 9;

    protected $notificationService = null;

    public function beforeSave(Entity $entity)
    {
        if ($entity->get('type') == 'Post') {
            //$post = $entity->get('post');
            $this->addMentionData($entity);
        }
    }

    /**
     * @param Entity $entity
     *
     * @since 1.0
     */
    protected function addMentionData($entity)
    {
        /**
         * @var User $user
         */
        $post = $entity->get('post');
        $mentionData = new \stdClass();
        $previousMentionList = array();
        if ($entity->isFetched()) {
            $data = $entity->get('data');
            if (!empty($data) && !empty($data->mentions)) {
                $previousMentionList = array_keys(get_object_vars($data->mentions));
            }
        }
        preg_match_all('/(@\w+)/', $post, $matches);
        if (is_array($matches) && !empty($matches[0]) && is_array($matches[0])) {
            foreach ($matches[0] as $item) {
                $userName = substr($item, 1);
                $user = $this->getEntityManager()->getRepository('User')->where(array('userName' => $userName))->findOne();
                if ($user) {
                    $m = array(
                        'id' => $user->id,
                        'name' => $user->get('name'),
                        'userName' => $user->get('userName'),
                        '_scope' => $user->getEntityName()
                    );
                    $mentionData->$item = (object)$m;
                    if (!in_array($item, $previousMentionList)) {
                        $this->notifyAboutMention($entity, $user);
                    }
                }
            }
        }
        $data = $entity->get('data');
        if (empty($data)) {
            $data = new \stdClass();
        }
        $data->mentions = $mentionData;
        $entity->set('data', $data);
    }

    protected function notifyAboutMention(Entity $entity, User $user)
    {
        $this->getNotificationService()->notifyAboutMentionInPost($user->id, $entity->id);
    }

    /**
     * @return Notification
     * @since 1.0
     */
    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->getServiceFactory()->create('Notification');
        }
        return $this->notificationService;
    }

    /**
     * @return ServiceFactory
     * @since 1.0
     */
    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function init()
    {
        $this->dependencies[] = 'serviceFactory';
    }
}

