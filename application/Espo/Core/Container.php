<?php
/* * **********************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 * ********************************************************************** */

namespace Espo\Core;

class Container
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Get object
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    /**
     * Set user
     *
     * @param \Espo\Entities\User $user
     *
     * @return void
     */
    public function setUser(\Espo\Entities\User $user)
    {
        $this->set('user', $user);
    }

    /**
     * Push object
     *
     * @param string $name
     * @param mixed $obj
     *
     * return void
     */
    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    /**
     * Load object
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function load($name)
    {
        $loadMethod = 'load'.ucfirst($name);
        if (method_exists($this, $loadMethod)) {
            $this->set($name, $this->$loadMethod());
        } else {

            try {
                $className = $this->get('metadata')->get('app.loaders.'.ucfirst($name));
            } catch (\Exception $e) {

            }

            if (!isset($className) || !class_exists($className)) {
                $className = '\Espo\Custom\Core\Loaders\\'.ucfirst($name);
                if (!class_exists($className)) {
                    $className = '\Espo\Core\Loaders\\'.ucfirst($name);
                }
            }

            if (class_exists($className)) {
                $this->set($name, (new $className($this))->load());
            }
        }

        return null;
    }

    /**
     * Load file manager
     *
     * @return \Espo\Core\Utils\File\Manager
     */
    protected function loadFileManager()
    {
        return new \Espo\Core\Utils\File\Manager($this->get('config'));
    }

    /**
     * Load config
     *
     * @return \Espo\Core\Utils\Config
     */
    protected function loadConfig()
    {
        return new \Espo\Core\Utils\Config(new \Espo\Core\Utils\File\Manager());
    }

    /**
     * Load metadata
     *
     * @return \Espo\Core\Utils\Metadata
     */
    protected function loadMetadata()
    {
        return new \Espo\Core\Utils\Metadata($this->get('fileManager'), $this->get('config')->get('useCache'));
    }
}
