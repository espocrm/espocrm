<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\File;

use Espo\Core\Utils\Util;

use Throwable;

class Permission
{

    /**
     * Last permission error.
     *
     * @var string[]
     */
    protected $permissionError = [];

    /**
     * @var ?array<string, mixed>
     */
    protected $permissionErrorRules = null;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected $writableMap = [
        'data' => [
            'recursive' => true,
        ],
        'client/custom' => [
            'recursive' => true,
        ],
        'custom/Espo/Custom' => [
            'recursive' => true,
        ],
        'custom/Espo/Modules' => [
            'recursive' => true,
        ],
    ];

    /**
     * @var array{
     *   dir: string|int|null,
     *   file: string|int|null,
     *   user: string|int|null,
     *   group: string|int|null,
     * }
     */
    protected $defaultPermissions = [
        'dir' => '0755',
        'file' => '0644',
        'user' => null,
        'group' => null,
    ];

    /**
     * @var array{
     *   file: string|int|null,
     *   dir: string|int|null,
     * }
     */
    protected $writablePermissions = [
        'file' => '0664',
        'dir' => '0775',
    ];

    /**
     * @param ?array<string, mixed> $params
     */
    public function __construct(private Manager $fileManager, ?array $params = null)
    {
        if ($params) {
            foreach ($params as $paramName => $paramValue) {
                switch ($paramName) {
                    case 'defaultPermissions':
                        /** @phpstan-ignore-next-line */
                        $this->defaultPermissions = array_merge($this->defaultPermissions, $paramValue);

                        break;
                }
            }
        }
    }
    /**
     * Get default settings.
     *
     * @return array{
     *   dir: string|int|null,
     *   file: string|int|null,
     *   user: string|int|null,
     *   group: string|int|null,
     * }
     */
    public function getDefaultPermissions(): array
    {
        return $this->defaultPermissions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getWritableMap(): array
    {
        return $this->writableMap;
    }

    /**
     * @return string[]
     */
    public function getWritableList(): array
    {
        return array_keys($this->writableMap);
    }

    /**
     * @return array{
     *   dir: string|int|null,
     *   file: string|int|null,
     *   user: string|int|null,
     *   group: string|int|null,
     * }
     */
    public function getRequiredPermissions(string $path): array
    {
        $permission = $this->getDefaultPermissions();

        foreach ($this->getWritableMap() as $writablePath => $writableOptions) {
            if (!$writableOptions['recursive'] && $path == $writablePath) {
                /** @phpstan-ignore-next-line */
                return array_merge($permission, $this->writablePermissions);
            }

            if ($writableOptions['recursive'] && str_starts_with($path, $writablePath)) {
                /** @phpstan-ignore-next-line */
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
     * @return string|false
     */
    public function getCurrentPermission(string $filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        /** @var array{mode: mixed} $fileInfo */
        $fileInfo = stat($filePath);

        return substr(base_convert((string) $fileInfo['mode'], 10, 8), -4);
    }

    /**
     * Change permissions.
     *
     * @param string $path
     * @param int|array<int|string, string|int|null>|string $octal Ex. `0755`, `[0644, 0755]`, `['file' => 0644, 'dir' => 0755]`.
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
        } else if (
            /** @phpstan-ignore-next-line  */
            is_int((int) $octal) // Always true. @todo Fix.
        ) {
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
     * @param int $fileOctal Ex. 0644.
     * @param int $dirOctal Ex. 0755.
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

        /** @var string[] $allFiles */
        $allFiles = $this->fileManager->getFileList($path);

        foreach ($allFiles as $item) {
            $result &= $this->chmodRecurse($path . Util::getSeparator() . $item, $fileOctal, $dirOctal);
        }

        return (bool) $result;
    }

    /**
     * Change owner permission.
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

        if ($user === false) {
            // @todo Revise.
            $user = '';
        }

        if (!$recurse) {
            return $this->chownReal($path, $user);
        }

        return $this->chownRecurse($path, $user);
    }

    /**
     * Change owner permission recursive.
     *
     * @param int|string $user
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

        /** @var string[] $allFiles */
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
     * @noinspection SpellCheckingInspection
     */
    public function chgrp(string $path, $group = null, bool $recurse = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!isset($group)) {
            $group = $this->getDefaultGroup();
        }

        if ($group === false) {
            // @todo Revise.
            $group = '';
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
     * @noinspection SpellCheckingInspection
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

        /** @var string[] $allFiles */
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

        $defaultOwner = $this->getDefaultOwner(true);
        $defaultGroup = $this->getDefaultGroup(true);

        if ($defaultOwner === false) {
            // @todo Revise.
            $defaultOwner = '';
        }

        if ($defaultGroup === false) {
            // @todo Revise.
            $defaultGroup = '';
        }

        $this->chown($filename, $defaultOwner);
        $this->chgrp($filename, $defaultGroup);

        return @chmod($filename, $mode);
    }

    /**
     * @param int|string $user
     */
    protected function chownReal(string $path, $user): bool
    {
        if (!function_exists('chown')) {
            return true;
        }

        return @chown($path, $user);
    }

    /**
     * @param int|string $group
     * @noinspection SpellCheckingInspection
     * @todo Revise the need of exception handling.
     *
     */
    protected function chgrpReal(string $path, $group): bool
    {
        if (!function_exists('chgrp')) {
            return true;
        }

        return @chgrp($path, $group);
    }

    /**
     * Get default owner user.
     *
     * @return string|int|false owner id.
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
     * @return string|int|false Group id.
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
            } catch (Throwable) {}

            /** check is writable */
            $res = is_writable($path);

            if (is_dir($path)) {
                try {
                    $name = uniqid();

                    $res &= $this->fileManager->putContents($path . '/' . $name, 'test');

                    $res &= $this->fileManager->removeFile($name, $path);
                } catch (Throwable) {
                    $res = false;
                }
            }

            if (!$res) {
                $result = false;

                $this->permissionError[] = $path;
                $this->permissionErrorRules[$path] = $this->writablePermissions;
            }
        }

        return $result;
    }

    /**
     * Get last permission error.
     *
     * @return string[]
     */
    public function getLastError()
    {
        return $this->permissionError;
    }

    /**
     * Get last permission error rules.
     *
     * @return ?array<string, array<string, string>>
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
     * @return string[]
     */
    public function arrangePermissionList(array $fileList): array
    {
        $betterList = [];

        foreach ($fileList as $fileName) {
            $pathInfo = pathinfo($fileName);
            /** @var string $dirname */
            $dirname = $pathInfo['dirname'] ?? null;

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
     * Get count of a search string in an array.
     *
     * @param string $search
     * @param string[] $array
     * @return int
     */
    protected function getSearchCount(string $search, array $array)
    {
        $searchQuoted = $this->getPregQuote($search);

        $number = 0;

        foreach ($array as $value) {
            if (preg_match('/^' . $searchQuoted . '/', $value)) {
                $number++;
            }
        }

        return $number;
    }

    /**
     * @param string[] $array
     */
    protected function itemIncludes(string $item, array $array): bool
    {
        foreach ($array as $value) {
            $value = $this->getPregQuote($value);

            if (preg_match('/^' . $value . '/', $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getPregQuote(string $string): string
    {
        return preg_quote($string, '/-+=.');
    }
}
