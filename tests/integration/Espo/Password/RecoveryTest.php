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

namespace tests\integration\Espo\Password;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Entities\Portal;
use Espo\Tools\UserSecurity\Password\Recovery\UrlValidator;
use tests\integration\Core\BaseTestCase;

class RecoveryTest extends BaseTestCase
{
    private ?string $storedSiteUrl = null;

    private string $siteUrl = 'https://my-site.com/';

    protected function setUp(): void
    {
        parent::setUp();

        $writer = $this->getInjectableFactory()->create(ConfigWriter::class);
        $writer->set('siteUrl', $this->siteUrl);
        $writer->save();

        $this->storedSiteUrl = $this->getConfig()->get('siteUrl');
    }

    protected function tearDown(): void
    {
        $writer = $this->getInjectableFactory()->create(ConfigWriter::class);
        $writer->set('siteUrl', $this->storedSiteUrl);
        $writer->save();

        $this->storedSiteUrl = null;

        parent::tearDown();
    }

    public function testUrlValidation()
    {
        $em = $this->getEntityManager();

        $em->createEntity(Portal::ENTITY_TYPE, [
            'customUrl' => 'https://my-portal.com/',
        ]);

        /** @var Portal $portal2 */
        $portal2 = $em->createEntity(Portal::ENTITY_TYPE, [
            'isDefault' => true,
        ]);

        $validator = $this->getInjectableFactory()->create(UrlValidator::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-site.com');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-site.com/');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-site.com#Test');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-site.com/portal');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-portal.com');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-portal.com/');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-portal.com/#Test');

        /** @noinspection PhpUnhandledExceptionInspection */
        $validator->validate('https://my-site.com/portal/' . $portal2->getId());

        $thrown = false;

        try {
            $validator->validate('https://not-my-site.com');
        }
        catch (Forbidden) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }
}
