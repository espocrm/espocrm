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

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Preferences extends \Espo\Core\ORM\Repository
{
    protected $dependencies = array(
        'fileManager',
        'metadata',
        'config',
    );
    
    protected $defaultAttributesFromSettings = array(
        'defaultCurrency',
        'dateFormat',
        'timeFormat',        
        'decimalMark',
        'thousandSeparator',
        'weekStart',
        'timeZone',
        'language',
        'exportDelimiter'
    );
    
    protected $data = array();
    
    protected $entityName = 'Preferences';
    
    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
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
                    $this->data[$id] = json_decode($this->getFileManager()->getContents($fileName), true);
                } else {
                    $fields = $this->getMetadata()->get('entityDefs.Preferences.fields');
                    $defaults = array();
                    $defaults['dashboardLayout'] = $this->getMetadata()->get('app.defaultDashboardLayout');
                    foreach ($fields as $field => $d) {
                        if (array_key_exists('default', $d)) {
                            $defaults[$field] = $d['default'];                            
                        }                        
                    }
                    foreach ($this->defaultAttributesFromSettings as $attr) {
                        $defaults[$attr] = $this->getConfig()->get($attr);
                    }
                    
                    $this->data[$id] = $defaults;
                }            
            }
            
            $entity->set($this->data[$id]);
            $d = $entity->toArray();
            return $entity;
        }        
    }
    
    public function save(Entity $entity)
    {
        if ($entity->id) {
            $this->data[$entity->id] = $entity->toArray();
            
            $fileName = $this->getFilePath($entity->id);
            $this->getFileManager()->putContents($fileName, json_encode($this->data[$entity->id]));
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

