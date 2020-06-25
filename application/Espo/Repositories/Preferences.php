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

namespace Espo\Repositories;

use Espo\ORM\Entity;
use Espo\ORM\Repository;
use Espo\Core\Utils\Json;

use Espo\ORM\Repositories\{
    Removable,
};

use Espo\Core\Di;

class Preferences extends Repository implements Removable,
    Di\MetadataAware,
    Di\ConfigAware,
    Di\EntityManagerAware
{
    use Di\MetadataSetter;
    use Di\ConfigSetter;
    use Di\EntityManagerSetter;

    protected $defaultAttributeListFromSettings = [
        'decimalMark',
        'thousandSeparator',
        'exportDelimiter',
        'followCreatedEntities',
    ];

    protected $data = [];

    protected $entityType = 'Preferences';

    public function get(?string $id = null) : ?Entity
    {
        if ($id) {
            $entity = $this->entityFactory->create('Preferences');
            $entity->id = $id;
            if (empty($this->data[$id])) {
                $pdo = $this->entityManager->getPDO();
                $sql = "SELECT `id`, `data` FROM `preferences` WHERE id = ".$pdo->quote($id);
                $ps = $pdo->query($sql);

                $data = null;

                $sth = $pdo->prepare($sql);
                $sth->execute();

                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    $data = Json::decode($row['data']);
                    $data = get_object_vars($data);
                    break;
                }

                if ($data) {
                    $this->data[$id] = $data;
                } else {
                    $fields = $this->metadata->get('entityDefs.Preferences.fields');
                    $defaults = [];

                    $dashboardLayout = $this->config->get('dashboardLayout');
                    $dashletsOptions = null;
                    if (!$dashboardLayout) {
                        $dashboardLayout = $this->metadata->get('app.defaultDashboardLayouts.Standard');
                        $dashletsOptions = $this->metadata->get('app.defaultDashboardOptions.Standard');
                    }

                    if ($dashletsOptions === null) {
                        $dashletsOptions = $this->config->get('dashletsOptions', (object) []);
                    }

                    $defaults['dashboardLayout'] = $dashboardLayout;
                    $defaults['dashletsOptions'] = $dashletsOptions;

                    foreach ($fields as $field => $d) {
                        if (array_key_exists('default', $d)) {
                            $defaults[$field] = $d['default'];
                        }
                    }
                    foreach ($this->defaultAttributeListFromSettings as $attr) {
                        $defaults[$attr] = $this->config->get($attr);
                    }

                    $this->data[$id] = $defaults;
                    $entity->set($defaults);
                }
            }

            $entity->set($this->data[$id]);

            $this->fetchAutoFollowEntityTypeList($entity);

            $entity->setAsFetched($this->data[$id]);

            return $entity;
        }
    }

    protected function fetchAutoFollowEntityTypeList(Entity $entity)
    {
        $id = $entity->id;

        $autoFollowEntityTypeList = [];
        $pdo = $this->entityManager->getPDO();
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
        $pdo = $this->entityManager->getPDO();
        $sql = "DELETE FROM autofollow WHERE user_id = ".$pdo->quote($id)."";
        $pdo->query($sql);

        $scopes = $this->metadata->get('scopes');
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

    public function save(Entity $entity, array $options = [])
    {
        if (!$entity->id) return;

        $this->data[$entity->id] = $entity->toArray();

        $fields = $fields = $this->metadata->get('entityDefs.Preferences.fields');

        $data = [];
        foreach ($this->data[$entity->id] as $field => $value) {
            if (empty($fields[$field]['notStorable'])) {
                $data[$field] = $value;
            }
        }

        $dataString = Json::encode($data, \JSON_PRETTY_PRINT);

        $pdo = $this->entityManager->getPDO();

        $sql = "
            INSERT INTO `preferences` (`id`, `data`) VALUES (".$pdo->quote($entity->id).", ".$pdo->quote($dataString).")
            ON DUPLICATE KEY UPDATE `data` = ".$pdo->quote($dataString)."
        ";

        $pdo->query($sql);

        $user = $this->entityManager->getEntity('User', $entity->id);
        if ($user && !$user->isPortal()) {
            $this->storeAutoFollowEntityTypeList($entity);
        }

        return $entity;
    }

    public function deleteFromDb(string $id)
    {
        $pdo = $this->entityManager->getPDO();
        $sql = "DELETE  FROM `preferences` WHERE `id` = " . $pdo->quote($id);
        $ps = $pdo->query($sql);
    }

    public function remove(Entity $entity, array $options = [])
    {
        if (!$entity->id) return;
        $this->deleteFromDb($entity->id);
        if (isset($this->data[$entity->id])) {
            unset($this->data[$entity->id]);
        }
    }

    public function resetToDefaults(string $userId)
    {
        $this->deleteFromDb($userId);
        if (isset($this->data[$userId])) {
            unset($this->data[$userId]);
        }
        if ($entity = $this->get($userId)) {
            return $entity->toArray();
        }
    }
}
