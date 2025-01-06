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

namespace Espo\Tools\ExportCustom;

use GuzzleHttp\Psr7\Utils;

use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Tools\EntityManager\NameUtil;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class ExportCustom
{
    private string $minVersion = '8.0.0';

    /** @var string[] */
    private array $metadataFolderList = [
        'scopes',
        'entityDefs',
        'clientDefs',
        'recordDefs',
        'selectDefs',
        'aclDefs',
        'entityAcl',
        'formula',
    ];

    public function __construct(
        private Metadata $metadata,
        private FileManager $fileManager,
        private NameUtil $nameUtil,
        private FileStorageManager $fileStorageManager,
        private EntityManager $entityManager
    ) {}

    public function process(Params $params): Result
    {
        $this->validate($params);

        $data = $this->createData($params);

        $this->createDir($data);
        $this->copy($data);
        $this->fixMetadata($params, $data);
        $this->filterLayouts($data);
        $this->createControllers($params, $data);
        $this->createModuleJson($data);
        $this->createManifest($params, $data);
        $attachmentId = $this->createAttachment($data);
        $this->cleanup($data);

        return new Result($attachmentId);
    }

    private function cleanup(Data $data): void
    {
        $this->fileManager->removeInDir($data->getDir(), true);
        $this->fileManager->removeFile($data->getDir() . '.zip');
    }

    private function createDir(Data $data): void
    {
        $this->fileManager->mkdir($data->getDestDir(), 0755);
    }

    private function copy(Data $data): void
    {
        $customDir = 'custom/Espo/Custom';

        $this->fileManager->copy(
            $customDir . '/Resources',
            $data->getDestDir() . '/Resources',
            true
        );
    }

    private function fixMetadata(Params $params, Data $data): void
    {
        foreach ($data->customEntityTypeList as $scope) {
            $file = $data->getDestDir() . '/Resources/metadata/scopes/' . $scope . '.json';

            if (!$this->fileManager->exists($file)) {
                throw new RuntimeException("File $file does not exist.");
            }

            $defs = Json::decode($this->fileManager->getContents($file));

            unset($defs->isCustom);

            $defs->module = $params->getModule();
            $defs->isCustomExported = true;

            $this->fileManager->putJsonContents($file, $defs);
        }

        $dir = $data->getDestDir() . '/Resources/metadata/entityDefs';

        /** @var string[] $files */
        $files = $this->fileManager->getFileList($dir);

        $files = array_filter($files, fn ($file) => str_ends_with($file, '.json'));

        foreach ($files as $file) {
            $fullFile = $dir . '/' . $file;

            $defs = Json::decode($this->fileManager->getContents($fullFile));

            foreach (get_object_vars($defs->fields ?? (object) []) as $key => $value) {
                unset($defs->fields->$key->isCustom);
            }

            foreach (get_object_vars($defs->links ?? (object) []) as $key => $value) {
                unset($defs->links->$key->isCustom);
            }

            $this->fileManager->putJsonContents($fullFile, $defs);
        }

        $metadataDir = $data->getDestDir() . '/Resources/metadata';

        /** @var string[] $list */
        $list = $this->fileManager->getFileList($metadataDir);

        foreach ($list as $item) {
            if (in_array($item, $this->metadataFolderList)) {
                continue;
            }

            $file = $metadataDir . '/' . $item;

            if ($this->fileManager->isFile($file)) {
                $this->fileManager->removeFile($file);

                continue;
            }

            $this->fileManager->removeInDir($file, true);
        }
    }

    private function filterLayouts(Data $data): void
    {
        $dir = $data->getDestDir() . '/Resources/layouts';

        /** @var string[] $list */
        $list = $this->fileManager->getFileList($dir);

        foreach ($list as $item) {
            if (in_array($item, $data->customEntityTypeList)) {
                continue;
            }

            $file = $dir . '/' . $item;

            if ($this->fileManager->isFile($file)) {
                $this->fileManager->removeFile($file);

                continue;
            }

            $this->fileManager->removeInDir($file, true);
        }
    }

    private function createControllers(Params $params, Data $data): void
    {
        foreach ($data->customEntityTypeList as $scope) {
            $this->createController($params, $data, $scope);
        }
    }

    private function createModuleJson(Data $data): void
    {
        $file = $data->getDestDir() . '/Resources/module.json';

        $defs = (object) ['order' => 30];

        $this->fileManager->putJsonContents($file, $defs);
    }

    private function createController(Params $params, Data $data, string $scope): void
    {
        $shortClassName = Util::normalizeClassName($scope);

        $module = $params->getModule();

        $contents = "<" . "?" . "php\n\n".
            "namespace Espo\\Modules\\$module\\Controllers;\n\n".
            "class $shortClassName extends \\Espo\\Core\\Templates\\Controllers\\Base\n".
            "{}\n";

        $file = "{$data->getDestDir()}/Controllers/$shortClassName.php";

        $this->fileManager->putContents($file, $contents);
    }

    private function createManifest(Params $params, Data $data): void
    {
        $file = $data->getDir() . '/manifest.json';

        $defs = (object) [
            'name' => $params->getName(),
            'version' => $params->getVersion(),
            'author' => $params->getAuthor(),
            'skipBackup' => true,
            'releaseDate' => DateTime::getSystemTodayString(),
            'description' => $params->getDescription(),
            'acceptableVersions' => ['>=' . $this->minVersion]
        ];

        $this->fileManager->putJsonContents($file, $defs);
    }

    private function createAttachment(Data $data): string
    {
        $zipFile = $this->createZip($data);

        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getNew();

        $attachment
            ->setName($data->folder . '.zip')
            ->setRole(Attachment::ROLE_EXPORT_FILE)
            ->setType('application/zip')
            ->setSize($this->fileManager->getSize($zipFile));

        $this->entityManager->saveEntity($attachment);

        $resource = fopen($zipFile, 'r');

        if ($resource === false) {
            throw new RuntimeException("Could not open file $zipFile.");
        }

        $this->fileStorageManager->putStream($attachment, Utils::streamFor($resource));

        return $attachment->getId();
    }

    private function createZip(Data $data): string
    {
        $zip = new ZipArchive();

        $archiveFile = $data->getDir() . '.zip';

        $openResult = $zip->open($archiveFile, ZipArchive::CREATE);

        if ($openResult !== true) {
            throw new RuntimeException("Could not open zip.");
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($data->getDir() . '/'),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file => $fileDescriptor) {
            $file = str_replace("\\", "/", $file);

            if (!$this->fileManager->isFile($file)) {
                continue;
            }

            $entry = substr($file, strlen('data/tmp/' . $data->folder . '/'));

            $zip->addFile(
                getcwd() . '/' . $file,
                $entry
            );
        }

        $zip->close();

        return $archiveFile;
    }

    private function createData(Params $params): Data
    {
        $folder = Util::camelCaseToHyphen($params->getModule()) . '-' . $params->getVersion();

        return new Data(
            folder: $folder,
            customEntityTypeList: $this->obtainCustomEntityTypeList(),
            module: $params->getModule(),
        );
    }

    private function validate(Params $params): void
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9 ]+$/', $params->getName())) {
            throw new RuntimeException("Bad extension name.");
        }

        if (!preg_match('/^[A-Z][a-z][A-Za-z]+$/', $params->getModule())) {
            throw new RuntimeException("Bad module name. Should be in CamelCase.");
        }

        if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $params->getVersion())) {
            throw new RuntimeException("Bad version number.");
        }

        if ($this->nameUtil->nameIsNotAllowed($params->getModule())) {
            throw new RuntimeException("Not allowed module name.");
        }

        $forbiddenModuleNames = [
            'Advanced',
            'AdvancedPack',
            'Sales',
            'SalesPack',
            'Google',
            'Outlook',
            'Voip',
        ];

        if (in_array($params->getModule(), $forbiddenModuleNames)) {
            throw new RuntimeException("Not allowed module name.");
        }
    }

    /**
     * @return string[]
     */
    private function obtainCustomEntityTypeList(): array
    {
        $list = [];

        /** @var array<string, array<string, mixed>> $scopes */
        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            $isCustom = $item['isCustom'] ?? null;
            $isEntity = $item['entity'] ?? null;

            if (!$isCustom || !$isEntity) {
                continue;
            }

            $list[] = $scope;
        }

        return $list;
    }
}
