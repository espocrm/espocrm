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

namespace tests\integration\Espo\Core\Utils;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use tests\integration\Core\BaseTestCase;

class MetadataTest extends BaseTestCase
{
    private $filePath1 = 'custom/Espo/Custom/Resources/metadata/app/rebuild.json';
    private $filePath2 = 'custom/Espo/Custom/Resources/metadata/recordDefs/Note.json';

    protected function tearDown(): void
    {
        $this->getFileManager()->removeFile($this->filePath1);
        $this->getFileManager()->removeFile($this->filePath2);

        parent::tearDown();
    }

    public function testAppend1()
    {
        $initial = $this->getMetadata()->get(['app', 'rebuild', 'actionClassNameList']);

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
                    "\\Espo\\Classes\\FieldProcessing\\Note\\AdditionalFieldsLoader",
                ]
            ]
        );

        $this->createDirForFile($this->filePath1);
        $this->getFileManager()->putContents($this->filePath1, $contents1);
        $this->getFileManager()->putContents($this->filePath2, $contents2);
        $this->getDataManager()->clearCache();

        $app = $this->createApplication();

        /** @var Metadata $metadata */
        $metadata = $app->getContainer()->get('metadata');

        $this->assertSame(
            array_merge(
                $initial,
                ["\\Espo\\Core\\Rebuild\\Actions\\ScheduledJobs"]
            ),
            $metadata->get(['app', 'rebuild', 'actionClassNameList'])
        );

        $this->assertSame(
            [
                "Espo\\Classes\\FieldProcessing\\Note\\AdditionalFieldsLoader",
                "\\Espo\\Classes\\FieldProcessing\\Note\\AdditionalFieldsLoader",
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
