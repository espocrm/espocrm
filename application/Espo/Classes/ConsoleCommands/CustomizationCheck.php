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

namespace Espo\Classes\ConsoleCommands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Utils\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @noinspection PhpUnused
 */
class CustomizationCheck implements Command
{
    private const CUSTOMIZATION_DIR = 'custom/Espo/';

    private const IGNORE_PATH_REGEX_LIST = [
        '^custom\/Espo\/Modules\/[^\/]+\/vendor',
        '^custom\/Espo\/Custom\/vendor',
    ];

    public function __construct(
        private Log $log
    ) {}

    public function run(Params $params, IO $io): void
    {
        $io->write('Customizations: ');

        if ($this->loadCustomizations()) {
            $this->writeOK($io);
        } else {
            $this->writeFail($io);
            $io->setExitStatus(1);
        }

        $io->writeLine('');
    }

    private function writeOK(IO $io): void
    {
        $io->write("\033[32mOK\033[0m");
    }

    private function writeFail(IO $io): void
    {
        $io->write("\033[31mFAIL\033[0m");
    }

    private function loadCustomizations(): bool
    {
        foreach (self::getFiles(self::CUSTOMIZATION_DIR) as $file) {
            $script = 'include \'bootstrap.php\';' .
                ' $app = new \Espo\Core\Application();' .
                ' require_once ' . var_export($file, true) . ';';

            $process = proc_open(
                [PHP_BINARY, '-r', $script],
                [1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );

            if ($process === false) {
                return false;
            }

            $error = stream_get_contents($pipes[2]);

            fclose($pipes[2]);

            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                $this->log->error(
                    "CustomizationCheck: class file '$file' failed." .
                    ($error !== '' && $error !== false ? ' ' . trim($error) : '')
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private static function getFiles(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        $files = [];

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = $file->getPathname();

            if (self::toIgnoreFile($class)) {
                continue;
            }

            $files[] = $class;
        }

        return $files;
    }

    private static function toIgnoreFile(string $file): bool
    {
        foreach (self::IGNORE_PATH_REGEX_LIST as $pattern) {
            if (preg_match('/' . $pattern . '/', $file)) {
                return true;
            }
        }

        return false;
    }
}
