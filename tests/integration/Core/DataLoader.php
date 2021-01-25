<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Core;

class DataLoader
{
    private $application;

    private $passwordHash;

    public function __construct($application)
    {
        $this->application = $application;

        $config = $this->getContainer()->get('config');
        $this->passwordHash = new \Espo\Core\Utils\PasswordHash($config);
    }

    protected function getApplication()
    {
        return $this->application;
    }

    protected function getContainer()
    {
        return $this->application->getContainer();
    }

    protected function getPasswordHash()
    {
        return $this->passwordHash;
    }

    public function loadData($dataFile)
    {
        if (!file_exists($dataFile)) {
            return;
        }

        $data = include($dataFile);
        $this->handleData($data);
    }

    public function setData(array $data)
    {
        $this->handleData($data);
    }

    protected function handleData(array $fullData)
    {
        foreach ($fullData as $type => $data) {
            $methodName = 'load' . ucfirst($type);
            if (!method_exists($this, $methodName)) {
                throw new \Exception('DataLoader: Data type is not supported in dataFile ['.$dataFile.'].');
            }

            $this->$methodName($data);
        }
    }

    public function loadFiles($path)
    {
        try {
            $fileManager = $this->getContainer()->get('fileManager');
            $fileManager->copy($path, '.', true);
        } catch (Exception $e) {
            throw new \Exception('Error loadFiles: ' . $e->getMessage());
        }
    }

    protected function loadEntities(array $data)
    {
        $entityManager = $this->getContainer()->get('entityManager');

        foreach($data as $entityName => $entities) {

            foreach($entities as $entityData) {
                $entity = $entityManager->getEntity($entityName, $entityData['id']);
                if (empty($entity)) {
                    $entity = $entityManager->getEntity($entityName);
                }

                foreach($entityData as $field => $value) {
                    if ($field == "password" && $entityName == "User") {
                        $value = $this->getPasswordHash()->hash($value);
                    }
                    $entity->set($field, $value);
                }

                try {
                    $entityManager->saveEntity($entity);
                } catch (\Exception $e) {
                    throw new \Exception('Error loadEntities: ' . $e->getMessage() . ', ' . print_r($entityData, true));
                }
            }
        }
    }

    protected function loadConfig(array $data)
    {
        if (empty($data)) {
            return;
        }

        $config = $this->getContainer()->get('config');
        $config->set($data);

        try {
            $config->save();
        } catch (\Exception $e) {
            throw new \Exception('Error loadConfig: ' . $e->getMessage());
        }
    }

    protected function loadPreferences(array $data)
    {
        $entityManager = $this->getContainer()->get('entityManager');

        foreach ($data as $userId => $params) {
            $entityManager->getRepository('Preferences')->resetToDefaults($userId);
            $preferences = $entityManager->getEntity('Preferences', $userId);
            $preferences->set($params);

            try {
                $entityManager->saveEntity($preferences);
            } catch (\Exception $e) {
                throw new \Exception('Error loadPreferences: ' . $e->getMessage());
            }
        }
    }

    protected function loadSql(array $data)
    {
        if (empty($data)) {
            return;
        }

        $pdo = $this->getContainer()->get('entityManager')->getPDO();

        foreach ($data as $sql) {
            try {
                $pdo->query($sql);
            } catch (Exception $e) {
                throw new \Exception('Error loadSql: ' . $e->getMessage() . ', sql: ' . $sql);
            }
        }
    }
}
