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

namespace Espo\Core\Console\Commands;

use Espo\Core\Exceptions\Error;

class Extension extends Base
{
    protected $extensionManager = null;

    public function run($options, $flagList, $argumentList)
    {
        if (in_array('u', $flagList)) {
            // uninstall

            $name = $options['name'] ?? null;
            $id = $options['id'] ?? null;
            if (!$name && !$id) {
                $this->out("Can't uninstall. Specify --name=\"Extension Name\".\n");
                return;
            }
            $params = [];
            if ($id) {
                $params['id'] = $id;
            } else {
                $params['name'] = $name;
            }
            $params['delete'] = !in_array('k', $flagList);

            $this->runUninstall($params);
            return;
        } else {
            // install

            $file = $options['file'] ?? null;
            if (!$file) {
                $this->out("Can't install. Specify --file=\"path/to/package.zip\".\n");
                return;
            }

            $this->runInstall($file);
            return;
        }
    }

    protected function runInstall(string $file)
    {
        $manager = $this->createExtensionManager();

        if (!file_exists($file)) {
            $this->out("File does not exist.\n");
            return;
        }

        $fileData = file_get_contents($file);
        $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

        try {
            $id = $manager->upload($fileData);
        } catch (\Throwable $e) {
            $this->out($e->getMessage() . "\n");
            return;
        }

        $manifest = $manager->getManifestById($id);

        $name = $manifest['name'] ?? null;
        $version = $manifest['version'] ?? null;

        if (!$name) {
            $this->out("Can't install. Bad manifest.\n");
            return;
        }

        $this->out("Installing... Do not close the terminal. This may take a while...");

        try {
            $manager->install(['id' => $id]);
        } catch (\Throwable $e) {
            $this->out("\n");
            $this->out($e->getMessage() . "\n");
            return;
        }

        $this->out("\n");
        $this->out("Extension '{$name}' version {$version} is installed.\nExtension ID: '{$id}'.\n");
    }

    protected function runUninstall(array $params)
    {
        $id = $params['id'] ?? null;

        if ($id) {
            $record = $this->getEntityManager()->getRepository('Extension')->where([
                'id' => $id,
                'isInstalled' => true,
            ])->findOne();

            if (!$record) {
                $this->out("Extension with ID '{$id}' is not installed.\n");
                return;
            }

            $name = $record->get('name');
        } else {
            $name = $params['name'] ?? null;
            if (!$name) {
                $this->out("Can't uninstall. No --name or --id specified.\n");
                return;
            }

            $record = $this->getEntityManager()->getRepository('Extension')->where([
                'name' => $name,
                'isInstalled' => true,
            ])->findOne();

            if (!$record) {
                $this->out("Extension '{$name}' is not installed.\n");
                return;
            }

            $id = $record->id;
        }

        $manager = $this->createExtensionManager();

        $this->out("Uninstalling... Do not close the terminal. This may take a while...");

        try {
            $manager->uninstall(['id' => $id]);
        } catch (\Throwable $e) {
            $this->out("\n");
            $this->out($e->getMessage() . "\n");
            return;
        }

        $this->out("\n");

        if ($params['delete'] ?? false) {
            try {
                $manager->delete(['id' => $id]);
            } catch (\Throwable $e) {
                $this->out($e->getMessage() . "\n");
                $this->out("Extension '{$name}' is uninstalled but could not be deleted.\n");
                return;
            }

            $this->out("Extension '{$name}' is uninstalled and deleted.\n");
        } else {
            $this->out("Extension '{$name}' is uninstalled.\n");
        }
    }

    protected function createExtensionManager()
    {
        return new \Espo\Core\ExtensionManager($this->getContainer());
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function out(string $string)
    {
        fwrite(\STDOUT, $string);
    }
}
