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

namespace Espo\Tools\EntityManager\Rename;

use Espo\Core\Console\IO;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Util;
use Espo\Core\DataManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Entities\PortalRole;
use Espo\Entities\Role;
use Espo\ORM\EntityManager;

use Espo\Tools\EntityManager\NameUtil;

use RuntimeException;
use Throwable;

class Renamer
{
    /**
     * @var array<int,ClassType::*>
     */
    private $classTypeList = [
        ClassType::ENTITY,
        ClassType::CONTROLLER,
        ClassType::REPOSITORY,
        ClassType::SELECT_MANAGER,
        ClassType::SERVICE,
    ];

    /**
     * @var array<int,MetadataType::*>
     */
    private $metadataTypeList = [
        MetadataType::ACL_DEFS,
        MetadataType::CLIENT_DEFS,
        MetadataType::ENTITY_DEFS,
        MetadataType::ENTITY_ACL,
        MetadataType::FORMULA,
        MetadataType::NOTIFICATION_DEFS,
        MetadataType::PDF_DEFS,
        MetadataType::RECORD_DEFS,
        MetadataType::SCOPES,
        MetadataType::SELECT_DEFS,
        MetadataType::LOGIC_DEFS,
    ];

    private NameUtil $nameUtil;

    private Metadata $metadata;

    private FileManager $fileManager;

    private EntityManager $entityManager;

    private DataManager $dataManager;

    private Config $config;

    private ConfigWriter $configWriter;

    private Language $language;

    private Log $log;

    public function __construct(
        NameUtil $nameUtil,
        Metadata $metadata,
        FileManager $fileManager,
        EntityManager $entityManager,
        DataManager $dataManager,
        Config $config,
        ConfigWriter $configWriter,
        Language $language,
        Log $log
    ) {
        $this->nameUtil = $nameUtil;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->dataManager = $dataManager;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->language = $language;
        $this->log = $log;
    }

    public function process(string $entityType, string $newName, IO $io): Result
    {
        $failResult = $this->validate($entityType, $newName);

        if ($failResult) {
            return $failResult;
        }

        $io->writeLine('  DB table...');
        $this->renameDbTable($entityType, $newName);

        $io->writeLine('  DB many-to-many tables...');
        $this->renameDbManyToMany($entityType, $newName);

        $io->writeLine('  metadata relationships...');
        $this->renameInRelationships($entityType, $newName);

        $io->writeLine('  metadata fields...');
        $this->renameInFields($entityType, $newName);

        $io->writeLine('  php classes...');
        foreach ($this->classTypeList as $classType) {
            $this->renameClass($classType, $entityType, $newName);
        }

        $io->writeLine('  metadata files...');
        foreach ($this->metadataTypeList as $metadataType) {
            $this->renameMetadata($metadataType, $entityType, $newName);
        }

        $io->writeLine('  layouts...');
        $this->renameLayouts($entityType, $newName);

        $io->writeLine('  values in DB...');
        $this->changeValuesInDb($entityType, $newName);

        $io->writeLine('  language...');
        $this->renameLanguage($entityType, $newName);

        $io->writeLine('  config parameters...');
        $this->renameInConfig($entityType, $newName);

        $io->writeLine('  rebuild...');
        $this->dataManager->rebuild();

        return Result::createSuccess();
    }

    private function validate(string $entityType, string $newName): ?Result
    {
        if (!$this->metadata->get(['scopes', $entityType, 'entity'])) {
            return Result::createFail(FailReason::DOES_NOT_EXIST);
        }

        if (!$this->metadata->get(['scopes', $entityType, 'isCustom'])) {
            return Result::createFail(FailReason::NOT_CUSTOM);
        }

        if ($this->nameUtil->nameIsBad($newName)) {
            return Result::createFail(FailReason::NAME_BAD);
        }

        /** @var non-empty-string $newName */

        if ($this->nameUtil->nameIsTooLong($newName)) {
            return Result::createFail(FailReason::NAME_TOO_LONG);
        }

        if ($this->nameUtil->nameIsTooShort($newName)) {
            return Result::createFail(FailReason::NAME_TOO_SHORT);
        }

        if ($this->nameUtil->nameIsNotAllowed($newName)) {
            return Result::createFail(FailReason::NAME_NOT_ALLOWED);
        }

        if ($this->nameUtil->nameIsUsed($newName)) {
            return Result::createFail(FailReason::NAME_USED);
        }

        if (!$this->fileManager->isFile($this->getClassFilePath(ClassType::CONTROLLER, $entityType))) {
            return Result::createFail(FailReason::NOT_CUSTOM);
        }

        if (!$this->fileManager->isFile($this->getMetadataFilePath(MetadataType::ENTITY_DEFS, $entityType))) {
            return Result::createFail(FailReason::NOT_CUSTOM);
        }

        if (!$this->fileManager->isFile($this->getMetadataFilePath(MetadataType::SCOPES, $entityType))) {
            return Result::createFail(FailReason::NOT_CUSTOM);
        }

        if ($this->tableExists($newName)) {
            return Result::createFail(FailReason::TABLE_EXISTS);
        }

        return null;
    }

    private function renameDbManyToMany(string $entityType, string $newName): void
    {
        $relationList = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getRelationList();

        foreach ($relationList as $relation) {
            if (!$relation->isManyToMany()) {
                continue;
            }

            if (lcfirst($relation->getRelationshipName()) === 'entityTeam') {
                continue;
            }

            $this->renameDbRelationshipTableColumn(
                $relation->getRelationshipName(),
                $entityType . 'Id',
                $newName . 'Id'
            );
        }
    }

    /**
     * @param ClassType::* $classType
     */
    private function renameClass(string $classType, string $name, string $newName): void
    {
        $path = $this->getClassFilePath($classType, $name);
        $newPath = $this->getClassFilePath($classType, $newName);

        if (!$this->fileManager->isFile($path)) {
            return;
        }

        $contents = $this->fileManager->getContents($path);

        $replaceFrom = [
            "class {$name}",
            "'{$name}'",
        ];

        $replaceTo = [
            "class {$newName}",
            "'{$newName}'",
        ];

        $newContents = str_replace($replaceFrom, $replaceTo, $contents);

        $this->fileManager->putContents($newPath, $newContents);
        $this->fileManager->removeFile($path);
    }

    /**
     * @param MetadataType::* $metadataType
     */
    private function renameMetadata(string $metadataType, string $name, string $newName): void
    {
        $path = $this->getMetadataFilePath($metadataType, $name);
        $newPath = $this->getMetadataFilePath($metadataType, $newName);

        if (!$this->fileManager->isFile($path)) {
            return;
        }

        $contents = $this->fileManager->getContents($path);

        $replaceFrom = [];
        $replaceTo = [];

        $newContents = str_replace($replaceFrom, $replaceTo, $contents);

        $this->fileManager->putContents($newPath, $newContents);
        $this->fileManager->removeFile($path);
    }

    /**
     * @param ClassType::* $classType
     */
    private function getClassFilePath(string $classType, string $name): string
    {
        return "custom/Espo/Custom/{$classType}/{$name}.php";
    }

    /**
     * @param MetadataType::* $metadataType
     */
    private function getMetadataFilePath(string $metadataType, string $name): string
    {
        return "custom/Espo/Custom/Resources/metadata/{$metadataType}/{$name}.json";
    }

    private function renameLayouts(string $name, string $newName): void
    {
        $fromPath = "custom/Espo/Custom/Resources/layouts/{$name}";
        $toPath = "custom/Espo/Custom/Resources/layouts/{$newName}";

        rename($fromPath, $toPath);
    }

    private function tableExists(string $newName): bool
    {
        $table = Util::camelCaseToUnderscore($newName);

        if (self::dbNameSanitize($table) !== $table) {
            throw new RuntimeException();
        }

        $sql = "SHOW TABLES LIKE '{$table}'";

        $sth = $this->entityManager->getSqlExecutor()->execute($sql);

        if ($sth->fetch()) {
            return true;
        }

        return false;
    }

    private function renameDbTable(string $name, string $newName): void
    {
        $table = Util::camelCaseToUnderscore($name);
        $newTable = Util::camelCaseToUnderscore($newName);

        if (self::dbNameSanitize($table) !== $table) {
            throw new RuntimeException();
        }

        if (self::dbNameSanitize($newTable) !== $newTable) {
            throw new RuntimeException();
        }

        $sql = "RENAME TABLE `{$table}` TO `{$newTable}`";

        $this->entityManager->getSqlExecutor()->execute($sql);
    }

    private function renameDbRelationshipTableColumn(string $entityType, string $name, string $newName): void
    {
        $table = Util::camelCaseToUnderscore($entityType);
        $column = Util::camelCaseToUnderscore($name);
        $newColumn = Util::camelCaseToUnderscore($newName);

        if (self::dbNameSanitize($table) !== $table) {
            throw new RuntimeException();
        }

        if (self::dbNameSanitize($column) !== $column) {
            throw new RuntimeException();
        }

        if (self::dbNameSanitize($newColumn) !== $newColumn) {
            throw new RuntimeException();
        }

        $sql = "ALTER TABLE `{$table}` CHANGE `{$column}` `{$newColumn}` VARCHAR(24)";

        try {
            $this->entityManager->getSqlExecutor()->execute($sql);
        } catch (Throwable $e) {
            $msg = $e->getMessage();

            $this->log->error("Entity-rename: Rename relationship column failed: {$msg}.");
        }
    }

    private static function dbNameSanitize(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $name) ?? '';
    }

    private function renameLanguage(string $entityType, string $newName): void
    {
        $label = $this->language->translateLabel($entityType, 'scopeNames');
        $labelPlural = $this->language->translateLabel($entityType, 'scopeNamesPlural');

        $this->language->delete('Global', 'scopeNames', $entityType);
        $this->language->delete('Global', 'scopeNamesPlural', $entityType);
        $this->language->set('Global', 'scopeNames', $newName, $label);
        $this->language->set('Global', 'scopeNamesPlural', $newName, $labelPlural);

        $this->language->save();

        $langList = $this->fileManager->getDirList('custom/Espo/Custom/Resources/i18n');

        foreach ($langList as $lang) {
            $fromPath = "custom/Espo/Custom/Resources/i18n/{$lang}/{$entityType}.json";
            $toPath = "custom/Espo/Custom/Resources/i18n/{$lang}/{$newName}.json";

            if (!$this->fileManager->isFile($fromPath)) {
                continue;
            }

            $contents = $this->fileManager->getContents($fromPath);

            $this->fileManager->putContents($toPath, $contents);
            $this->fileManager->removeFile($fromPath);
        }
    }

    private function renameInConfig(string $entityType, string $newName): void
    {
        /** @var string[] $entityTypeListParamList */
        $entityTypeListParamList = $this->metadata->get(['app', 'config', 'entityTypeListParamList']) ?? [];

        foreach ($entityTypeListParamList as $param) {
            /** @var ?string[] $list */
            $list = $this->config->get($param);

            if ($list === null) {
                continue;
            }

            $index = array_search($entityType, $list, true);

            if ($index === false) {
                continue;
            }

            $list[$index] = $newName;

            $this->configWriter->set($param, $list);
        }

        $this->configWriter->save();
    }

    private function renameInRelationships(string $entityType, string $newName): void
    {
        /** @var string[] $entityTypeList */
        $entityTypeList = array_keys($this->metadata->get(['entityDefs']) ?? []);

        foreach ($entityTypeList as $itemEntityType) {
            $this->renameInRelationshipsEntity($itemEntityType, $entityType, $newName);
        }

        $this->metadata->save();
    }

    private function renameInRelationshipsEntity(string $entityType, string $fromEntityType, string $toEntityType): void
    {
        /** @var array<string, array<string, mixed>> $linkDefs */
        $linkDefs = $this->metadata->get(['entityDefs', $entityType, 'links']) ?? [];

        foreach ($linkDefs as $link => $defs) {
            $itemEntityType = $defs['entity'] ?? null;

            if ($itemEntityType !== $fromEntityType) {
                continue;
            }

            $this->metadata->set('entityDefs', $entityType, [
                'links' => [
                    $link => [
                        'entity' => $toEntityType,
                    ]
                ]
            ]);
        }
    }

    private function renameInFields(string $entityType, string $newName): void
    {
        $entityList = $this->entityManager
            ->getDefs()
            ->getEntityList();

        foreach ($entityList as $entityDefs) {
            foreach ($entityDefs->getFieldNameList() as $field) {
                $this->renameInFieldsField($entityDefs->getName(), $field, $entityType, $newName);
            }
        }

        $this->metadata->save();
    }

    private function renameInFieldsField(string $entityType, string $field, string $from, string $to): void
    {
        $defs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getField($field);

        if ($defs->getType() === FieldType::LINK_PARENT) {
            $this->renameInLinkParentField($entityType, $defs->getName(), $from, $to);
        }
    }

    private function renameInLinkParentField(string $entityType, string $field, string $from, string $to): void
    {
        /** @var string[] $list */
        $list = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'entityList']) ?? [];

        $index = array_search($from, $list, true);

        if ($index === false) {
            return;
        }

        $list[$index] = $to;

        $this->metadata->set('entityDefs', $entityType, [
            'fields' => [
                $field => ['entityList' => $list]
            ]
        ]);
    }

    private function changeValuesInDb(string $from, string $to): void
    {
        $this->changeValuesInDbFields($from, $to);
        $this->changeValuesInDbRoles($from, $to);

        $query1 = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('EntityTeam')
            ->set(['entityType' => $to])
            ->where(['entityType' => $from])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query1);

        $query2 = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('EntityEmailAddress')
            ->set(['entityType' => $to])
            ->where(['entityType' => $from])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query2);

        $query3 = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('EntityPhoneNumber')
            ->set(['entityType' => $to])
            ->where(['entityType' => $from])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query3);
    }

    private function changeValuesInDbFields(string $from, string $to): void
    {
        $entityList = $this->entityManager
            ->getDefs()
            ->getEntityList();

        foreach ($entityList as $entityDefs) {
            foreach ($entityDefs->getFieldNameList() as $field) {
                $this->changeValuesInDbFieldsField($entityDefs->getName(), $field, $from, $to);
            }
        }
    }

    private function changeValuesInDbFieldsField(string $entityType, string $field, string $from, string $to): void
    {
        $defs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getField($field);

        if ($defs->getType() === FieldType::LINK_PARENT) {
            $this->changeValuesInDbFieldsFieldLinkParent($entityType, $defs->getName(), $from, $to);
        }
    }

    private function changeValuesInDbFieldsFieldLinkParent(
        string $entityType,
        string $field,
        string $from,
        string $to
    ): void {

        $typeAttribute = $field . 'Type';

        $query = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in($entityType)
            ->set([$typeAttribute => $to])
            ->where([$typeAttribute => $from])
            ->build();

        try {
            $this->entityManager->getQueryExecutor()->execute($query);
        } catch (Throwable $e) {
            $msg = $e->getMessage();

            $this->log->error("Entity-rename: Update values in link-parent field {$entityType}.{$field}: {$msg}.");
        }
    }

    private function changeValuesInDbRoles(string $from, string $to): void
    {
        $roleList = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->find();

        foreach ($roleList as $role) {
            $data = $role->getRawData();
            $fieldData = $role->getRawFieldData();

            if (isset($data->$from)) {
                $data->$to = $data->$from;
                unset($data->$from);


                $role->set('data', $data);
            }

            if (isset($fieldData->$from)) {
                $fieldData->$to = $fieldData->$from;
                unset($fieldData->$from);

                $role->set('fieldData', $fieldData);
            }

            $this->entityManager->saveEntity($role);
        }

        /** @var iterable<PortalRole> $portalRoleList */
        $portalRoleList = $this->entityManager
            ->getRDBRepositoryByClass(PortalRole::class)
            ->find();

        foreach ($portalRoleList as $role) {
            $data = $role->getRawData();
            $fieldData = $role->getRawFieldData();

            if (isset($data->$from)) {
                $data->$to = $data->$from;
                unset($data->$from);

                $role->set('data', $data);
            }

            if (isset($fieldData->$from)) {
                $fieldData->$to = $fieldData->$from;
                unset($fieldData->$from);

                $role->set('fieldData', $fieldData);
            }

            $this->entityManager->saveEntity($role);
        }
    }
}
