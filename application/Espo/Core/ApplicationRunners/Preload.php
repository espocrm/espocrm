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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\Application\Runner;
use Espo\Core\Utils\Preload as PreloadUtil;

use Throwable;

/**
 * Runs a preload.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class Preload implements Runner
{
    use Cli;

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $preload = new PreloadUtil();

        try {
            $preload->process();
        } catch (Throwable $e) {
            $this->processException($e);

            throw $e;
        }

        $count = $preload->getCount();

        echo "Success." . PHP_EOL;
        echo "Files loaded: " . $count . "." . PHP_EOL;
    }

    protected function processException(Throwable $e): void
    {
        echo "Error occurred." . PHP_EOL;

        $msg = $e->getMessage();

        if ($msg) {
            echo "Message: $msg" . PHP_EOL;
        }

        $file = $e->getFile();

        if ($file) {
            echo "File: $file" . PHP_EOL;
        }

        echo "Line: " . $e->getLine() . PHP_EOL;
    }
}
