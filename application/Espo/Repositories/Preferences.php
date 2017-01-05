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

namespace Espo\Repositories;

use Espo\ORM\Entity;
use Espo\Core\Utils\Json;

class Preferences extends \Espo\Core\ORM\Repository
{
    protected $defaultAttributeListFromSettings = array(
        'decimalMark',
        'thousandSeparator',
        'exportDelimiter'
    );

    protected $data = array();

    protected $entityType = 'Preferences';

    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'fileManager',
            'metadata',
            'config',
            'entityManager'
        ]);
    }

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getEntityManger()
    {
        return $this->getInjection('entityManager');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getFilePath($id)
    {
        return 'data/preferences/' . $id . '.json';
    }

    public function get($id = null)
    {
        if ($id) {
            $entity = $this->entityFactory->create('Preferences');
            $entity->id = $id;
            if (empty($this->data[$id])) {
                $fileName = $this->getFilePath($id);
                if (file_exists($fileName)) {
                    $d = Json::decode($this->getFileManager()->getContents($fileName));
                    $this->data[$id] = get_object_vars($d);
                } else {
                    $fields = $this->getMetadata()->get('entityDefs.Preferences.fields');
                    $defaults = array();

                    $dashboardLayout = $this->getConfig()->get('dashboardLayout');
                    $dashletsOptions = null;
                    if (!$dashboardLayout) {
                        $dashboardLayout = $this->getMetadata()->get('app.defaultDashboardLayouts.Standard');
                        $dashletsOptions = $this->getMetadata()->get('app.defaultDashboardOptions.Standard');
                    }

                    if ($dashletsOptions === null) {
                        $dashletsOptions = $this->getConfig()->get('dashletsOptions', (object) []);
                    }

                    $defaults['dashboardLayout'] = $dashboardLayout;
                    $defaults['dashletsOptions'] = $dashletsOptions;

                    foreach ($fields as $field => $d) {
                        if (array_key_exists('default', $d)) {
                            $defaults[$field] = $d['default'];
                        }
                    }
                    foreach ($this->defaultAttributeListFromSettings as $attr) {
                        $defaults[$attr] = $this->getConfig()->get($attr);
                    }
                    $this->data[$id] = $defaults;
                }
            }

            $entity->set($this->data[$id]);


            $this->fetchAutoFollowEntityTypeList($entity);


            $entity->setAsFetched($this->data[$id]);

            $d = $entity->toArray();
            return $entity;
        }
    }

    protected function fetchAutoFollowEntityTypeList(Entity $entity)
    {
        $id = $entity->id;

        $autoFollowEntityTypeList = [];
        $pdo = $this->getEntityManger()->getPDO();
        $sql = "
            SELECT `entity_type` AS 'entityType' FROM `autofollow`
            WHERE `user_id` = ".$pdo->quote($id)."
            ORDER BY `entity_type`
        ";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll();
        foreach ($rows as $row) {
            $autoFollowEntityTypeList[] = $row['entityType'];
        }
        $this->data[$id]['autoFollowEntityTypeList'] = $autoFollowEntityTypeList;
        $entity->set('autoFollowEntityTypeList', $autoFollowEntityTypeList);
    }

    protected function storeAutoFollowEntityTypeList(Entity $entity)
    {
        $id = $entity->id;

        $was = $entity->getFetched('autoFollowEntityTypeList');
        $became = $entity->get('autoFollowEntityTypeList');

        if (!is_array($was)) {
            $was = [];
        }
        if (!is_array($became)) {
            $became = [];
        }

        if ($was == $became) {
            return;
        }
        $pdo = $this->getEntityManger()->getPDO();
        $sql = "DELETE FROM autofollow WHERE user_id = ".$pdo->quote($id)."";
        $pdo->query($sql);

        $scopes = $this->getMetadata()->get('scopes');
        foreach ($became as $entityType) {
            if (isset($scopes[$entityType]) && !empty($scopes[$entityType]['stream'])) {
                $sql = "
                    INSERT INTO autofollow (user_id, entity_type)
                    VALUES (".$pdo->quote($id).", ".$pdo->quote($entityType).")
                ";
                $pdo->query($sql);
            }
        }
    }

    public function save(Entity $entity)
    {
        if ($entity->id) {
            $this->data[$entity->id] = $entity->toArray();

            $fields = $fields = $this->getMetadata()->get('entityDefs.Preferences.fields');

            $data = array();
            foreach ($this->data[$entity->id] as $field => $value) {
                if (empty($fields[$field]['notStorable'])) {
                    $data[$field] = $value;
                }
            }

            $fileName = $this->getFilePath($entity->id);
            $this->getFileManager()->putContents($fileName, Json::encode($data, \JSON_PRETTY_PRINT));

            $user = $this->getEntityManger()->getEntity('User', $entity->id);
            if ($user && !$user->get('isPortalUser')) {
                $this->storeAutoFollowEntityTypeList($entity);
            }

            return $entity;
        }
    }

    public function remove(Entity $entity)
    {
        $fileName = $this->getFilePath($id);
        unlink($fileName);
        if (!file_exists($fileName)) {
            return true;
        }
    }

    public function resetToDefaults($userId)
    {
        $fileName = $this->getFilePath($userId);
        $this->getFileManager()->unlink($fileName);
        if ($entity = $this->get($userId)) {
            return $entity->toArray();
        }
    }

    public function find(array $params)
    {
    }

    public function findOne(array $params)
    {
    }

    public function getAll()
    {
    }

    public function count(array $params)
    {
    }
}

