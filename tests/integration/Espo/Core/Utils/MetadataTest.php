<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Core\Utils;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;

class MetadataTest extends \tests\integration\Core\BaseTestCase
{
    private $filePath1 = 'custom/Espo/Custom/Resources/metadata/app/rebuild.json';
    private $filePath2 = 'custom/Espo/Custom/Resources/metadata/recordDefs/Note.json';

    protected function tearDown(): void
    {
        $this->getFileManager()->removeFile($this->filePath1);
        $this->getFileManager()->removeFile($this->filePath2);

        parent::tearDown();
    }

    /**
     * @throws \Espo\Core\Exceptions\Error
     */
    public function testAppend1()
    {
        $contents1 = Json::encode(
            (object) [
                'actionClassNameList' => [
                    "\\Espo\\Core\\Rebuild\\Actions\\ScheduledJobs",
                ]
            ]
        );

        $contents2 = Json::encode(
            (object) [
                'readLoaderClassNameList' => [
                    "\\Espo\\Classes\\FieldProcessing\\Note\\AttachmentsLoader",
                ]
            ]
        );

        $this->createDirForFile($this->filePath1);
        $this->getFileManager()->putContents($this->filePath1, $contents1);
        $this->getFileManager()->putContents($this->filePath2, $contents2);
        $this->getDataManager()->clearCache();

        $app = $this->createApplication();

        /** @var Metadata */
        $metadata = $app->getContainer()->get('metadata');

        $this->assertSame(
            [
                "Espo\\Core\\Rebuild\\Actions\\ScheduledJobs",
                "\\Espo\\Core\\Rebuild\\Actions\\ScheduledJobs",
            ],
            $metadata->get(['app', 'rebuild', 'actionClassNameList'])
        );

        $this->assertSame(
            [
                "Espo\\Classes\\FieldProcessing\\Note\\AttachmentsLoader",
                "\\Espo\\Classes\\FieldProcessing\\Note\\AttachmentsLoader",
            ],
            $metadata->get(['recordDefs', 'Note', 'readLoaderClassNameList'])
        );

        $this->getFileManager()->removeFile($this->filePath1);
        $this->getFileManager()->removeFile($this->filePath2);
        $this->getDataManager()->clearCache();
    }

    private function createDirForFile(string $filePath): void
    {
        $prevFolder = '';

        foreach (array_slice(explode('/', $filePath), 0, -1) as $folder) {
            $prevFolder .= $folder . '/';

            $this->getFileManager()->mkdir(substr($prevFolder, 0, -1));
        }
    }
}
