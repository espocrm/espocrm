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
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\File\Permission;

use Espo\ORM\EntityManager;

class AfterUpgrade
{
    public function run(Container $container): void
    {
        $entityManager = $container->get('entityManager');

        $this->updateTemplates($entityManager);

        $this->updateEventMetadata($container->get('metadata'), $container->get('fileManager'));
        $this->updatePersonMetadata($container->get('metadata'), $container->get('fileManager'));
        $this->updateCompanyMetadata($container->get('metadata'), $container->get('fileManager'));

        $this->migrateEmailAccountFolders('EmailAccount', $container->get('entityManager'));
        $this->migrateEmailAccountFolders('InboundEmail', $container->get('entityManager'));


        try {
            $this->updateConfig(
                $container->get('config'),
                $container->get('injectableFactory')->create(ConfigWriter::class)
            );
        }
        catch (\Throwable $e) {}

        $this->setPermissions(
            $container->get('injectableFactory')->create(Permission::class)
        );
    }

    protected function updateTemplates($entityManager)
    {
        $templateList = $entityManager
            ->getRepository('Template')
            ->where([
                'printHeader' => false,
                ['header!=' => null],
                ['header!=' => ''],
            ])
            ->find();

        foreach ($templateList as $template) {
            $body = $template->get('header') . ($template->get('body') ?? '');

            $template->set('body', $body);
            $template->set('header', null);

            $entityManager->saveEntity($template, ['skipHooks' => true]);
        }
    }

    private function updateEventMetadata(Metadata $metadata, FileManager $fileManager): void
    {
        $defs = $metadata->get(['scopes']);

        $toSave = false;

        $path1 = "application/Espo/Core/Templates/Metadata/Event/selectDefs.json";

        $contents1 = $fileManager->getContents($path1);

        $data1 = Json::decode($contents1, true);

        $path2 = "application/Espo/Core/Templates/Metadata/Event/recordDefs.json";
        $contents2 = $fileManager->getContents($path2);
        $data2 = Json::decode($contents2, true);

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom || $type !== 'Event') {
                continue;
            }

            $toSave = true;

            $metadata->set('selectDefs', $entityType, $data1);
            $metadata->set('recordDefs', $entityType, $data2);
        }

        if ($toSave) {
            $metadata->save();
        }
    }

    private function updatePersonMetadata(Metadata $metadata, FileManager $fileManager): void
    {
        $defs = $metadata->get(['scopes']);

        $toSave = false;

        $path2 = "application/Espo/Core/Templates/Metadata/Person/recordDefs.json";

        $contents2 = $fileManager->getContents($path2);

        $data2 = Json::decode($contents2, true);

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom || $type !== 'Person') {
                continue;
            }

            $toSave = true;

            $metadata->set('recordDefs', $entityType, $data2);
        }

        if ($toSave) {
            $metadata->save();
        }
    }

    private function updateCompanyMetadata(Metadata $metadata, FileManager $fileManager): void
    {
        $defs = $metadata->get(['scopes']);

        $toSave = false;

        $path2 = "application/Espo/Core/Templates/Metadata/Company/recordDefs.json";

        $contents2 = $fileManager->getContents($path2);

        $data2 = Json::decode($contents2, true);

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom || $type !== 'Company') {
                continue;
            }

            $toSave = true;

            $metadata->set('recordDefs', $entityType, $data2);
        }

        if ($toSave) {
            $metadata->save();
        }
    }

    private function migrateEmailAccountFolders(string $entityType, EntityManager $entityManager): void
    {
        $selectQuery = $entityManager->getQueryBuilder()
            ->select()
            ->from($entityType)
            ->select(['id', 'monitoredFolders'])
            ->build();

        $sth = $entityManager->getQueryExecutor()->execute($selectQuery);

        $dataList = [];

        while ($row = $sth->fetch()) {
            $dataList[] = [
                'id' => $row['id'],
                'folders' => $row['monitoredFolders'] ?? '',
            ];
        }

        foreach ($dataList as $item) {
            $id = $item['id'];
            $foldersString = $item['folders'];

            $folders = array_map(
                function (string $item): string {
                    return trim($item);
                },
                explode(',', $foldersString)
            );

            $foldersJsonString = json_encode($folders);

            $updateQuery = $entityManager->getQueryBuilder()
                ->update()
                ->in($entityType)
                ->set(['monitoredFolders' => $foldersJsonString])
                ->where(['id' => $id])
                ->build();

            $entityManager->getQueryExecutor()->execute($updateQuery);
        }
    }

    private function updateConfig(Config $config, ConfigWriter $configWriter): void
    {
        $map = [];

        $itemList = [
            'cryptKey',
            'hashSecretKey',
            'apiSecretKeys',
            'database',
            'internalSmtpPassword',
            'passwordSalt',
            'webSocketSslCertificateLocalPrivateKey',
        ];

        foreach ($itemList as $item) {
            if (!$config->has($item)) {
                continue;
            }

            $map[$item] = $config->get($item);
        }

        $configWriter->setMultiple($map);
        $configWriter->save();
    }

    private function setPermissions(Permission $permission): void
    {
        $permission->chmod('bin/command', '0754');
    }
}
