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

namespace Espo\Core\Upgrades\Migrations\V9_1;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Upgrades\Migration\Script;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\ORM\EntityManager;
use Espo\Tools\Email\Util;

class AfterUpgrade implements Script
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function run(): void
    {
        $this->processKbArticles();
    }

    private function processKbArticles(): void
    {
        if (!str_starts_with(php_sapi_name(), 'cli')) {
            return;
        }

        $articles = $this->entityManager
            ->getRDBRepositoryByClass(KnowledgeBaseArticle::class)
            ->sth()
            ->select([
                'id',
                'body',
                'bodyPlain',
            ])
            ->limit(0, 3000)
            ->find();

        foreach ($articles as $article) {
            $plain = Util::stripHtml($article->getBody() ?? '') ?: null;

            $article->set('bodyPlain', $plain);

            $this->entityManager->saveEntity($article, [SaveOption::SKIP_HOOKS => true]);
        }
    }
}
