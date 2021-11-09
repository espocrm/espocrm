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

namespace Espo\Core\Utils\File;

use Espo\Core\Utils\Util;

use Throwable;

class Permission
{
    private $fileManager;

    /**
     * Last permission error.
     *
     * @var array|string
     */
    protected $permissionError = null;

    protected $permissionErrorRules = null;

    protected $writableMap = [
        'data' => [
            'recursive' => true,
        ],
        'application/Espo/Modules' => [
            'recursive' => false,
        ],
        'client/custom' => [
            'recursive' => true,
        ],
        'client/modules' => [
            'recursive' => false,
        ],
        'custom/Espo/Custom' => [
            'recursive' => true,
        ],
    ];

    protected $defaultPermissions = [
        'dir' => '0755',
        'file' => '0644',
        'user' => null,
        'group' => null,
    ];

    protected $writablePermissions = [
        'file' => '0664',
        'dir' => '0775',
    ];

    public function __construct(Manager $fileManager, array $params = null)
    {
        $this->fileManager = $fileManager;

        if ($params) {
            foreach ($params as $paramName => $paramValue) {
                switch ($paramName) {
                    case 'defaultPermissions':
                        $this->defaultPermissions = array_merge($this->defaultPermissions, $paramValue);

                        break;
                }
            }
        }
    }
    /**
     * Get default settings.
     */
    public function getDefaultPermissions(): array
    {
        return $this->defaultPermissions;
    }

    public function getWritableMap(): array
    {
        return $this->writableMap;
    }

    public function getWritableList(): array
    {
        return array_keys($this->writableMap);
    }

    public function getRequiredPermissions(string $path): array
    {
        $permission = $this->getDefaultPermissions();

        foreach ($this->getWritableMap() as $writablePath => $writableOptions) {
            if (!$writableOptions['recursive'] && $path == $writablePath) {
                return array_merge($permission, $this->writablePermissions);
            }

            if ($writableOptions['recursive'] && substr($path, 0, strlen($writablePath)) == $writablePath) {
                return array_merge($permission, $this->writablePermissions);
            }
        }

        return $permission;
    }

    /**
     * Set default permission.
     */
    public function setDefaultPermissions(string $path, bool $recurse = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $permission = $this->getRequiredPermissions($path);

        $result = $this->chmod($path, [$permission['file'], $permission['dir']], $recurse);

        if (!empty($permission['user'])) {
            $result &= $this->chown($path, $permission['user'], $recurse);
        }

        if (!empty($permission['group'])) {
            $result &= $this->chgrp($path, $permission['group'], $recurse);
        }

        return (bool) $result;
    }

    /**
     * Get current permissions.
     *
     * @return string|bool
     */
    public function getCurrentPermission(string $filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileInfo = stat($filePath);

        return substr(base_convert((string) $fileInfo['mode'], 10, 8), -4);
    }

    /**
     * Change permissions.
     *
     * @param string $path
     * @param int|string[]|string $octal ex. `0755`, `[0644, 0755]`, `['file' => 0644, 'dir' => 0755]`.
     * @param bool $recurse
     */
    public function chmod(string $path, $octal, bool $recurse = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        /** @phpstan-var mixed $octal */

        $permission = [];

        if (is_array($octal)) {
            $count = 0;

            $rule = ['file', 'dir'];

            foreach ($octal as $key => $val) {
                $pKey = strval($key);

                if (!in_array($pKey, $rule)) {
                    $pKey = $rule[$count];
                }

                if (!empty($pKey)) {
                    $permission[$pKey]= $val;
                }

                $count++;
            }
        }
        else if (is_int((int) $octal)) { // Always true. @todo Fix.
            $permission = [
                'file' => $octal,
                'dir' => $octal,
            ];
        }

        // Convert to octal value.
        foreach ($permission as $key => $val) {
            if (is_string($val)) {
                $permission[$key] = base_convert($val, 8, 10);
            }
        }

        if (!$recurse) {
            if (is_dir($path)) {
                return $this->chmodReal($path, $permission['dir']);
            }

            return $this->chmodReal($path, $permission['file']);
        }

        return $this->chmodRecurse($path, $permission['file'], $permission['dir']);
    }

    /**
     * Change permissions recursive.
     *
     * @param int $fileOctal ex. 0644
     * @param int $dirOctal ex. 0755
     */
    protected function chmodRecurse(string $path, $fileOctal = 0644, $dirOctal = 0755): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!is_dir($path)) {
            return $this->chmodReal($path, $fileOctal);
        }

        $result = $this->chmodReal($path, $dirOctal);

        $allFiles = $this->fileManager->getFileList($path);

        foreach ($allFiles as $item) {
            $result &= $this->chmodRecurse($path . Util::getSeparator() . $item, $fileOctal, $dirOctal);
        }

        return (bool) $result;
    }

    /**
     * Change owner permission
     *
     * @param int|string $user
     */
    public function chown(string $path, $user = '', bool $recurse = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (empty($user)) {
            $user = $this->getDefaultOwner();
        }

        if (!$recurse) {
            return $this->chownReal($path, $user);
        }

        return $this->chownRecurse($path, $user);
    }

    /**
     * Change owner permission recursive.
     */
    protected function chownRecurse(string $path, $user): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!is_dir($path)) {
            return $this->chownReal($path, $user);
        }

        $result = $this->chownReal($path, $user);

        $allFiles = $this->fileManager->getFileList($path);

        foreach ($allFiles as $item) {
            $result &= $this->chownRecurse($path . Util::getSeparator() . $item, $user);
        }

        return (bool) $result;
    }

    /**
     * Change group permission.
     *
     * @param int|string $group
     */
    public function chgrp(string $path, $group = null, bool $recurse = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!isset($group)) {
            $group = $this->getDefaultGroup();
        }

        if (!$recurse) {
            return $this->chgrpReal($path, $group);
        }

        return $this->chgrpRecurse($path, $group);
    }

    /**
     * Change group permission recursive.
     *
     * @param int|string $group
     */
    protected function chgrpRecurse(string $path, $group): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!is_dir($path)) {
            return $this->chgrpReal($path, $group);
        }

        $result = $this->chgrpReal($path, $group);

        $allFiles = $this->fileManager->getFileList($path);

        foreach ($allFiles as $item) {
            $result &= $this->chgrpRecurse($path . Util::getSeparator() . $item, $group);
        }

        return (bool) $result;
    }

    /**
     * @param int $mode
     */
    protected function chmodReal(string $filename, $mode): bool
    {
        $result = @chmod($filename, $mode);

        if ($result) {
            return true;
        }

        $this->chown($filename, $this->getDefaultOwner(true));
        $this->chgrp($filename, $this->getDefaultGroup(true));

        return @chmod($filename, $mode);
    }

    /**
     * @param int|string $user
     */
    protected function chownReal(string $path, $user): bool
    {
        return @chown($path, $user);
    }

    /**
     * @todo Revise the need of exception handling.
     *
     * @param int|string $group
     */
    protected function chgrpReal(string $path, $group): bool
    {
        return @chgrp($path, $group);
    }

    /**
     * Get default owner user.
     *
     * @return int|false owner id.
     */
    public function getDefaultOwner(bool $usePosix = false)
    {
        $defaultPermissions = $this->getDefaultPermissions();

        $owner = $defaultPermissions['user'];

        if (empty($owner) && $usePosix) {
            $owner = function_exists('posix_getuid') ? posix_getuid() : null;
        }

        if (empty($owner)) {
            return false;
        }

        return $owner;
    }

    /**
     * Get default group user.
     *
     * @return int|false Group id.
     */
    public function getDefaultGroup(bool $usePosix = false)
    {
        $defaultPermissions = $this->getDefaultPermissions();

        $group = $defaultPermissions['group'];

        if (empty($group) && $usePosix) {
            $group = function_exists('posix_getegid') ? posix_getegid() : null;
        }

        if (empty($group)) {
            return false;
        }

        return $group;
    }

    /**
     * Set permission regarding defined in permissionMap.
     */
    public function setMapPermission(): bool
    {
        $this->permissionError = [];
        $this->permissionErrorRules = [];

        $result = true;

        foreach ($this->getWritableMap() as $path => $options) {
            if (!file_exists($path)) {
                continue;
            }

            try {
                $this->chmod($path, $this->writablePermissions, $options['recursive']);
            }
            catch (Throwable $e) {}

            /** check is writable */
            $res = is_writable($path);

            if (is_dir($path)) {
                try {
                    $name = uniqid();

                    $res &= $this->fileManager->putContents($path . '/' . $name, 'test');

                    $res &= $this->fileManager->removeFile($name, $path);
                }
                catch (Throwable $e) {
                    $res = false;
                }
            }

            if (!$res) {
                $result = false;

                $this->permissionError[] = $path;
                $this->permissionErrorRules[$path] = $this->writablePermissions;
            }
        }

        return (bool) $result;
    }

    /**
     * Get last permission error.
     *
     * @return array|string
     */
    public function getLastError()
    {
        return $this->permissionError;
    }

    /**
     * Get last permission error rules.
     *
     * @return array|string
     */
    public function getLastErrorRules()
    {
        return $this->permissionErrorRules;
    }

    /**
     * Arrange permission file list.
     *
     * e.g.
     * ```
     * [
     *     'application/Espo/Controllers/Email.php',
     *     'application/Espo/Controllers/Import.php',
     * ]
     * ```
     * result will be `['application/Espo/Controllers']`.
     *
     * @param string[] $fileList
     * @return array
     */
    public function arrangePermissionList(array $fileList): array
    {
        $betterList = [];

        foreach ($fileList as $fileName) {

            $pathInfo = pathinfo($fileName);
            $dirname = $pathInfo['dirname'];

            $currentPath = $fileName;

            if ($this->getSearchCount($dirname, $fileList) > 1) {
                $currentPath = $dirname;
            }

            if (!$this->itemIncludes($currentPath, $betterList)) {
                $betterList[] = $currentPath;
            }
        }

        return $betterList;
    }

    /**
     * Get count of a search string in a array.
     *
     * @param string $search
     * @param array $array
     * @return int
     */
    protected function getSearchCount($search, array $array)
    {
        $search = $this->getPregQuote($search);

        $number = 0;

        foreach ($array as $value) {
            if (preg_match('/^' . $search . '/', $value)) {
                $number++;
            }
        }

        return $number;
    }

    protected function itemIncludes($item, $array): bool
    {
        foreach ($array as $value) {
            $value = $this->getPregQuote($value);

            if (preg_match('/^' . $value . '/', $item)) {
                return true;
            }
        }

        return false;
    }

    protected function getPregQuote($string)
    {
        return preg_quote($string, '/-+=.');
    }
}
