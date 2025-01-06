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
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Template;
use Espo\ORM\EntityManager;
use Espo\Tools\Pdf\Template as PdfTemplate;

/** @noinspection PhpMultipleClassDeclarationsInspection */
class AfterUpgrade
{
    public function run(Container $container): void
    {
        $configWriter = $container->getByClass(InjectableFactory::class)
            ->create(ConfigWriter::class);

        $configWriter->setMultiple([
            'jobForceUtc' => true,
        ]);

        $configWriter->save();

        $em = $container->getByClass(EntityManager::class);
        $config = $container->getByClass(Config::class);

        $this->updateTemplates($em, $config);
        $this->updateTargetList($container->getByClass(Metadata::class));
    }

    private function updateTemplates(EntityManager $entityManager, Config $config): void
    {
        if ($config->get('pdfEngine') !== 'Dompdf') {
            return;
        }

        /** @var iterable<Template> $templates */
        $templates = $entityManager->getRDBRepositoryByClass(Template::class)
            ->sth()
            ->where(['pageFormat' => PdfTemplate::PAGE_FORMAT_CUSTOM])
            ->find();

        foreach ($templates as $template) {
            $width = $template->get('pageWidth') ?? 0.0;
            $height = $template->get('pageHeight') ?? 0.0;

            $template->setMultiple([
                'pageWidth' => $width / 2.83465,
                'pageHeight' => $height / 2.83465,
            ]);

            $entityManager->saveEntity($template);
        }
    }

    private function updateTargetList(Metadata $metadata): void
    {
        $links = $metadata->get('entityDefs.TargetList.links') ?? [];

        $toSave = false;

        foreach ($links as $link => $defs) {
            if (empty($defs['isCustom'])) {
                continue;
            }

            if (!$metadata->get("clientDefs.TargetList.relationshipPanels.$link.massSelect")) {
                continue;
            }

            $metadata->set('recordDefs', 'TargetList', [
                'relationships' => [
                    $link => [
                        'massLink' => true,
                        'linkRequiredForeignAccess' => 'read',
                        'mandatoryAttributeList' => ['targetListIsOptedOut'],
                    ]
                ]
            ]);

            $toSave = true;
        }

        if (!$toSave) {
            return;
        }

        $metadata->save();
    }
}
