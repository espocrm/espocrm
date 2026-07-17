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

namespace Espo\Core\Upgrades\Migrations\V10_1;

use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Theme\Direction;
use Espo\Entities\LeadCapture;
use Espo\Entities\Portal;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class AfterUpgrade implements Script
{
    private const LEGACY_THEME = 'EspoRtl';
    private const THEME = 'Espo';

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private ConfigWriter $configWriter,
    ) {}

    public function run(): void
    {
        $this->updateConfig();
        $this->updatePreferences();
        $this->updatePortals();
        $this->updateLeadCaptures();
    }

    private function updateConfig(): void
    {
        if ($this->config->get('theme') !== self::LEGACY_THEME) {
            return;
        }

        $this->configWriter->set('theme', self::THEME);
        $this->configWriter->set('themeParams', $this->prepareThemeParams($this->config->get('themeParams')));
        $this->configWriter->save();
    }

    private function updatePreferences(): void
    {
        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                User::ATTR_TYPE => [
                    User::TYPE_ADMIN,
                    User::TYPE_REGULAR,
                    User::TYPE_PORTAL,
                ],
            ])
            ->find();

        foreach ($users as $user) {
            $preferences = $this->entityManager
                ->getRepositoryByClass(Preferences::class)
                ->getById($user->getId());

            if (!$preferences || $preferences->get('theme') !== self::LEGACY_THEME) {
                continue;
            }

            $this->updateThemeEntity($preferences);
        }
    }

    private function updatePortals(): void
    {
        $portals = $this->entityManager
            ->getRDBRepositoryByClass(Portal::class)
            ->where(['theme' => self::LEGACY_THEME])
            ->find();

        foreach ($portals as $portal) {
            $this->updateThemeEntity($portal);
        }
    }

    private function updateLeadCaptures(): void
    {
        $leadCaptures = $this->entityManager
            ->getRDBRepositoryByClass(LeadCapture::class)
            ->where(['formTheme' => self::LEGACY_THEME])
            ->find();

        foreach ($leadCaptures as $leadCapture) {
            $leadCapture->set('formTheme', self::THEME);
            $this->entityManager->saveEntity($leadCapture);
        }
    }

    private function updateThemeEntity(Entity $entity): void
    {
        $entity->set('theme', self::THEME);
        $entity->set('themeParams', $this->prepareThemeParams($entity->get('themeParams')));

        $this->entityManager->saveEntity($entity);
    }

    private function prepareThemeParams(mixed $themeParams): object
    {
        if (is_object($themeParams)) {
            $themeParams = get_object_vars($themeParams);
        }

        if (!is_array($themeParams)) {
            $themeParams = [];
        }

        $themeParams['navbar'] ??= 'top';
        $themeParams['direction'] = Direction::Rtl->value;

        return (object) $themeParams;
    }
}
