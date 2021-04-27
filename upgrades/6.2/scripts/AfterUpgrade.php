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

use Espo\Core\Container;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;

class AfterUpgrade
{
    public function run(Container $container): void
    {
        $entityManager = $container->get('entityManager');

        $this->updateTemplates($entityManager);

        $this->updateEventMetadata($container->get('metadata'), $container->get('fileManager'));
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

            $entityManager->saveEntity($template);
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
}
