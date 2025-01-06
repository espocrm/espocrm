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

namespace tests\unit\Espo\Core\Mail;

use Espo\Core\Mail\Importer\Data as ImporterData;

use Espo\Entities\EmailFilter;

class ImporterDataTest extends \PHPUnit\Framework\TestCase
{

    function testData1()
    {
        $filter = $this->createMock(EmailFilter::class);

        $data = ImporterData
            ::create()
            ->withTeamIdList(['t1'])
            ->withUserIdList(['u1'])
            ->withAssignedUserId('a1')
            ->withFetchOnlyHeader(true)
            ->withFolderData(['t' => '1'])
            ->withFilterList([$filter]);

        $this->assertEquals(['t1'], $data->getTeamIdList());
        $this->assertEquals(['u1'], $data->getUserIdList());
        $this->assertEquals('a1', $data->getAssignedUserId());
        $this->assertEquals(true, $data->fetchOnlyHeader());
        $this->assertEquals(['t' => '1'], $data->getFolderData());
        $this->assertEquals([$filter], $data->getFilterList());
    }

    function testData2()
    {

        $data = ImporterData
            ::create()
            ->withFetchOnlyHeader(false);

        $this->assertEquals([], $data->getTeamIdList());
        $this->assertEquals([], $data->getUserIdList());
        $this->assertEquals(null, $data->getAssignedUserId());
        $this->assertEquals(false, $data->fetchOnlyHeader());
        $this->assertEquals([], $data->getFilterList());
    }
}
