<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\unit\Espo\Tools\Pdf\Util;

use Espo\Core\Acl;
use Espo\Entities\Attachment;
use Espo\Entities\Template;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\RDBRepository;
use Espo\Tools\Pdf\Util\Validation;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testValidation(): void
    {
        $template = $this->createMock(Template::class);

        $template->expects(self::any())
            ->method('isAttributeChanged')
            ->willReturnMap([
                [Template::FIELD_BODY, true],
                [Template::FIELD_HEADER, false],
                [Template::FIELD_FOOTER, false],
            ]);

        $acl = $this->createMock(Acl::class);

        $entityManager = $this->createMock(EntityManager::class);

        $repo = $this->createMock(RDBRepository::class);

        $entityManager->expects(self::any())
            ->method('getRDBRepositoryByClass')
            ->willReturnMap([
                [Attachment::class, $repo],
            ]);

        $template->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [Template::FIELD_BODY, "<img src=\"?entryPoint=attachment&id=test-id\">"],
            ]);

        $template->expects(self::any())
            ->method('getFetched')
            ->willReturnMap([
                [Template::FIELD_BODY, null],
            ]);

        $attachment = $this->createMock(Attachment::class);

        $repo->expects(self::once())
            ->method('getById')
            ->with('test-id')
            ->willReturn($attachment);

        $acl->expects(self::once())
            ->method('checkEntityRead')
            ->with($attachment)
            ->willReturn(true);

        $validation = new Validation(
            acl: $acl,
            entityManager: $entityManager,
        );

        $validation->process($template);
    }
}
