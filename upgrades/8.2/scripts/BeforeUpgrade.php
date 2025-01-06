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

use Espo\Core\Container;
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

        $this->checkRepositories($container->getByClass(EntityManager::class));
    }

    /**
     * @throws Exception
     */
    private function checkRepositories(EntityManager $em): void
    {
        /** @var class-string[] $classNameList */
        $classNameList = [
            "Espo\\Repositories\\ActionHistoryRecord",
            "Espo\\Repositories\\NextNumber",
            "Espo\\Repositories\\AuthLogRecord",
            "Espo\\Repositories\\AuthToken",
            "Espo\\Modules\\Crm\\Repositories\\Account",
            "Espo\\Modules\\Crm\\Repositories\\Call",
            "Espo\\Modules\\Crm\\Repositories\\CaseObj",
            "Espo\\Modules\\Crm\\Repositories\\Contact",
            "Espo\\Modules\\Crm\\Repositories\\KnowledgeBaseArticle",
            "Espo\\Modules\\Crm\\Repositories\\Lead",
            "Espo\\Modules\\Crm\\Repositories\\Meeting",
            "Espo\\Modules\\Crm\\Repositories\\Opportunity",
            "Espo\\Modules\\Crm\\Repositories\\TargetList",
            "Espo\\Modules\\Crm\\Repositories\\Task",
        ];

        $list = [];

        foreach ($em->getMetadata()->getEntityTypeList() as $entityType) {
            if (!$em->hasRepository($entityType)) {
                continue;
            }

            $repository = $em->getRepository($entityType);

            if (in_array(get_class($repository), $classNameList)) {
                continue;
            }

            foreach ($classNameList as $className) {
                if (is_a($repository, $className)) {
                    $list[] = get_class($repository);
                }
            }
        }

        if ($list === []) {
            return;
        }

        $msg = implode(', ', $list) .
            " should extend from Espo\\Core\\Repositories\\Database. Fix before upgrading.";

        throw new Exception($msg);
    }

    /**
     * @throws Error
     */
    private function processCheckExtensions(): void
    {
        $errorMessageList = [];

        $this->processCheckExtension('Advanced Pack', '3.1.0', $errorMessageList);
        $this->processCheckExtension('Real Estate', '1.8.0', $errorMessageList);

        if (!count($errorMessageList)) {
            return;
        }

        $message = implode("\n\n", $errorMessageList);

        throw new Error($message);
    }

    private function processCheckExtension(string $name, string $minVersion, array &$errorMessageList): void
    {
        $em = $this->container->get('entityManager');

        $extension = $em->getRDBRepository('Extension')
            ->where([
                'name' => $name,
                'isInstalled' => true,
            ])
            ->findOne();

        if (!$extension) {
            return;
        }

        $version = $extension->get('version');

        if (version_compare($version, $minVersion, '>=')) {
            return;
        }

        $message =
            "EspoCRM 8.2 is not compatible with '$name' extension of versions lower than $minVersion. " .
            "Please upgrade the extension or uninstall it.";

        $errorMessageList[] = $message;
    }
}
