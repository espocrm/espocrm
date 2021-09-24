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

namespace Espo\Core\Console\Commands;

use Espo\Core\{
    InjectableFactory,
    Utils\File\Manager as FileManager,
    Console\Command,
    Console\Command\Params,
    Console\IO,
};

use Espo\Core\Utils\Util;

class AppInfo implements Command
{
    private $injectableFactory;

    private $fileManager;

    public function __construct(InjectableFactory $injectableFactory, FileManager $fileManager)
    {
        $this->injectableFactory = $injectableFactory;
        $this->fileManager = $fileManager;
    }

    public function run(Params $params, IO $io): void
    {
        $fileList = $this->fileManager->getFileList('application/Espo/Classes/AppInfo');

        $typeList = array_map(
            function ($item) {
                return lcfirst(substr($item, 0, -4));
            },
            $fileList
        );

        foreach ($typeList as $type) {
            if ($params->hasFlag(Util::camelCaseToHyphen($type))) {
                $this->processType($io, $type, $params);

                return;
            }
        }

        if (count($params->getFlagList()) === 0) {
            $io->writeLine("");
            $io->writeLine("Available flags:");
            $io->writeLine("");

            foreach ($typeList as $type) {
                $io->writeLine(' --' . Util::camelCaseToHyphen($type));
            }

            $io->writeLine("");

            return;
        }

        $io->writeLine("Not supported flag specified.");
    }

    protected function processType(IO $io, string $type, Params $params): void
    {
        $className = 'Espo\\Classes\\AppInfo\\' . ucfirst($type);

        $obj = $this->injectableFactory->create($className);

        $result = $obj->process($params);

        $io->writeLine('');
        $io->write($result);
        $io->writeLine("");
    }
}
