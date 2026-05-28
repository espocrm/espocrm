<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Container;
use Espo\Core\Record\Service;
use Espo\Entities\Extension;
use Espo\ORM\EntityManager;

/** @noinspection PhpMultipleClassDeclarationsInspection */
class BeforeUpgrade
{
    private ?Container $container = null;

    /**
     * @throws Exception
     */
    public function run(Container $container): void
    {
        $this->container = $container;

        $this->processCheckExtensions();
        $this->processCheckCustomizations();
    }

    /**
     * @throws Error
     */
    private function processCheckExtensions(): void
    {
        $errorMessageList = [];

        $this->processCheckExtension('Advanced Pack', '3.13.0', $errorMessageList);
        $this->processCheckExtension('Real Estate', '1.8.5', $errorMessageList);

        if (!count($errorMessageList)) {
            return;
        }

        $message = implode("\n\n", $errorMessageList);

        throw new Error($message);
    }

    /**
     * @noinspection PhpSameParameterValueInspection
     */
    private function processCheckExtension(string $name, string $minVersion, array &$errorMessageList): void
    {
        $em = $this->container->getByClass(EntityManager::class);

        $extension = $em->getRDBRepositoryByClass(Extension::class)
            ->where([
                'name' => $name,
                'isInstalled' => true,
            ])
            ->findOne();

        if (!$extension) {
            return;
        }

        $version = $extension->getVersion();

        if (version_compare($version, $minVersion, '>=')) {
            return;
        }

        $message =
            "EspoCRM 10.0 is not compatible with '$name' extension of versions lower than $minVersion. " .
            "You need to upgrade the extension.";

        $errorMessageList[] = $message;
    }

    private function processCheckCustomizations(): void
    {
        /** @var array{0: string, 1: string[]}[] $data */
        $data = [
            [
                Service::class,
                [
                    'create',
                    'read',
                    'update',
                    'delete',
                    'link',
                    'unlink',
                    'massLink',
                    'beforeCreateEntity',
                    'afterCreateEntity',
                    'beforeUpdateEntity',
                    'afterUpdateEntity',
                    'beforeDeleteEntity',
                    'afterDeleteEntity',
                ]
            ],
        ];

        $output = [];

        foreach (self::getClasses("custom/Espo/") as $class) {
            foreach ($data as $it) {
                $base = $it[0];
                $methods = $it[1];

                try {
                    $isSubClass = class_exists($class) && is_subclass_of($class, $base);
                } catch (Throwable) {
                    continue;
                }

                if (
                    $isSubClass &&
                    (
                        str_starts_with($class, "Espo\\Modules\\") ||
                        str_starts_with($class, "Espo\\Custom\\")
                    ) &&
                    !str_starts_with($class, "Espo\\Modules\\Crm\\")
                ) {
                    foreach ($methods as $method) {
                        if (self::isMethodOverridden($class, $base, $method)) {
                            $output[] = [$class, $method];
                        }
                    }
                }
            }
        }

        if ($output === []) {
            return;
        }

        $message = "Cannot upgrade because of incompatible method overrides. ".
            "Fix the customizations and/or uninstall the incompatible extensions. " .
            "You can fix it by removing the methods for the classes.\n\n";

        $message .= "Problem classes and methods:\n";

        foreach ($output as $it) {
            $message .= ' ' . $it[0] . ': ' . $it[1] . "\n";
        }

        throw new Error($message);
    }

    /**
     * @noinspection PhpSameParameterValueInspection
     */
    private static function isMethodOverridden(string $child, string $base, string $method): bool
    {
        if (!class_exists($child) || !method_exists($child, $method)) {
            return false;
        }

        return (new ReflectionMethod($child, $method))->getDeclaringClass()->getName() !== $base;
    }

    /**
     * @return string[]
     * @noinspection PhpSameParameterValueInspection
     */
    private static function getClasses(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        $classes = [];

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            $namespace = null;
            $class = null;

            $tokens = token_get_all($content);

            for ($i = 0; $i < count($tokens); $i++) {
                $token = $tokens[$i];

                if (!is_array($token)) {
                    continue;
                }

                if ($token[0] === T_NAMESPACE) {
                    $namespace = '';

                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if (
                            is_array($tokens[$j]) &&
                            in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR])
                        ) {
                            $namespace .= $tokens[$j][1];
                        } elseif ($tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($token[0] === T_CLASS) {
                    $prev = $tokens[$i - 1] ?? null;

                    if (is_array($prev) && $prev[0] === T_NEW) {
                        continue;
                    }

                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                            $class = $tokens[$j][1];

                            break;
                        }
                    }

                    if ($class) {
                        $classes[] = $namespace
                            ? $namespace . '\\' . $class
                            : $class;
                    }
                }
            }
        }

        return $classes;
    }
}
