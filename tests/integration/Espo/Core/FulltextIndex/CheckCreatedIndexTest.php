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

namespace tests\integration\Espo\Core\FulltextIndex;

use Espo\Core\Utils\Util;
use PHPUnit\Framework\Attributes\DataProvider;
use tests\integration\Core\BaseTestCase;

class CheckCreatedIndexTest extends BaseTestCase
{
    protected ?string $dataFile = 'InitData.php';
    protected ?string $pathToFiles = 'Core/FulltextIndex/customFiles';

    static public function entityList()
    {
        return [
            ['Email'],
            ['Account'],
            ['Contact'],
        ];
    }

    #[DataProvider('entityList')]
    public function testCreatedIndexes($entityName)
    {
        $entityManager = $this->getContainer()->get('entityManager');
        $pdo = $entityManager->getPDO();

        $fulltextFieldList = $entityManager->getMetadata()->get($entityName, 'fullTextSearchColumnList');

        if (!$fulltextFieldList) {
            $this->assertNull($fulltextFieldList);
            return;
        }

        $query = "SHOW INDEX FROM `". Util::toCamelCase($entityName) ."` WHERE Index_type = 'FULLTEXT'";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $rowList = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($rowList);

        $result = [];
        foreach ($rowList as $row) {
            $result[] = Util::toCamelCase($row['Column_name']);
        }

        asort($fulltextFieldList);
        asort($result);

        $this->assertEquals($fulltextFieldList, $result);
    }
}
