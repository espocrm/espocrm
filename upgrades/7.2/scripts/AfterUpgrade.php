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

use Espo\Core\Container;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Config;

class AfterUpgrade
{
    public function run(Container $container): void
    {
        $this->updateEventMetadata($container->get('metadata'), $container->get('fileManager'));
        $this->updateTheme($container->get('entityManager'), $container->get('config'));
        $this->updateKbArticles($container->get('entityManager'));
    }

    private function updateEventMetadata(Metadata $metadata, FileManager $fileManager): void
    {
        $defs = $metadata->get(['scopes']);

        $toSave = false;

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom || $type !== 'Event') {
                continue;
            }

            $toSave = true;

            $metadata->set('recordDefs', $entityType, [
                'beforeUpdateHookClassNameList' => [
                    "__APPEND__",
                    "Espo\\Classes\\RecordHooks\\Event\\BeforeUpdatePreserveDuration"
                ]
            ]);

            $metadata->set('clientDefs', $entityType, [
                'forcePatchAttributeDependencyMap' => [
                    "dateEnd" => ["dateStart"],
                    "dateEndDate" => ["dateStartDate"]
                ]
            ]);

            if ($metadata->get(['entityDefs', $entityType, 'fields', 'isAllDay'])) {
                $metadata->set('entityDefs', $entityType, [
                    'fields' => [
                        'isAllDay' => [
                            'readOnly' => false,
                        ],
                    ]
                ]);
            }
        }

        if ($toSave) {
            $metadata->save();
        }
    }

    private function updateTheme(EntityManager $entityManager, Config $config): void
    {
        $themeList = [
            'EspoVertical',
            'HazyblueVertical',
            'VioletVertical',
            'SakuraVertical',
            'DarkVertical',
        ];

        $theme = $config->get('theme');
        $navbar = 'top';

        if (in_array($theme, $themeList)) {
            $theme = substr($theme, 0, -8);
            $navbar = 'side';
        }

        $config->set('theme', $theme);
        $config->set('themeParams', (object) ['navbar' => $navbar]);
        $config->save();

        $userList = $entityManager->getRDBRepository(\Espo\Entities\User::ENTITY_TYPE)
            ->where([
                'type' => ['regular', 'admin']
            ])
            ->find();

        foreach ($userList as $user) {
            $preferences = $entityManager->getEntityById(\Espo\Entities\Preferences::ENTITY_TYPE, $user->getId());

            if (!$preferences) {
                continue;
            }

            $theme = $preferences->get('theme');
            $navbar = 'top';

            if (!$theme) {
                continue;
            }

            if (in_array($theme, $themeList)) {
                $theme = substr($theme, 0, -8);
                $navbar = 'side';
            }

            $preferences->set('theme', $theme);
            $preferences->set('themeParams', (object) ['navbar' => $navbar]);

            $entityManager->saveEntity($preferences);
        }

        $portalList = $entityManager->getRDBRepository(\Espo\Entities\Portal::ENTITY_TYPE)
            ->where([
                'theme!=' => null,
            ])
            ->find();

        foreach ($portalList as $portal) {
            $theme = $portal->get('theme');
            $navbar = 'top';

            if (in_array($theme, $themeList)) {
                $theme = substr($theme, 0, -8);
                $navbar = 'side';
            }

            $portal->set('theme', $theme);
            $portal->set('themeParams', (object) ['navbar' => $navbar]);

            $entityManager->saveEntity($portal);
        }
    }

     private function updateKbArticles(EntityManager $entityManager): void
     {
         $query = $entityManager
             ->getQueryBuilder()
             ->update()
             ->in(\Espo\Modules\Crm\Entities\KnowledgeBaseArticle::ENTITY_TYPE)
             ->where(['type' => null])
             ->set(['type' => 'Article'])
             ->build();

         $entityManager->getQueryExecutor()->execute($query);
     }
}
