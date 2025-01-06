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

namespace Espo\Classes\FieldProcessing\LeadCapture;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Util;
use Espo\Entities\LeadCapture;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Type\AttributeType;

/**
 * @implements Loader<LeadCapture>
 */
class ExampleLoader implements Loader
{
    public function __construct(
        private FieldUtil $fieldUtil,
        private ApplicationConfig $applicationConfig,
        private EntityManager $entityManager
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $entity->set('exampleRequestMethod', 'POST');

        $entity->set('exampleRequestHeaders', [
            'Content-Type: application/json',
        ]);

        $this->processRequestUrl($entity);
        $this->processRequestPayload($entity);
        $this->processFormUrl($entity);
    }

    private function processRequestUrl(LeadCapture $entity): void
    {
        $apiKey = $entity->getApiKey();
        $siteUrl = $this->applicationConfig->getSiteUrl();

        if (!$apiKey) {
            return;
        }

        $requestUrl = "$siteUrl/api/v1/LeadCapture/$apiKey";

        $entity->set('exampleRequestUrl', $requestUrl);
    }

    private function processRequestPayload(LeadCapture $entity): void
    {
        $requestPayload = "```\n{\n";

        $attributeList = [];

        $attributeIgnoreList = [
            'emailAddressIsOptedOut',
            'phoneNumberIsOptedOut',
            'emailAddressIsInvalid',
            'phoneNumberIsInvalid',
            'emailAddressData',
            'phoneNumberData',
        ];

        foreach ($entity->getFieldList() as $field) {
            foreach ($this->fieldUtil->getActualAttributeList(Lead::ENTITY_TYPE, $field) as $attribute) {
                if (!in_array($attribute, $attributeIgnoreList)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        $seed = $this->entityManager->getNewEntity(Lead::ENTITY_TYPE);

        foreach ($attributeList as $i => $attribute) {
            $value = strtoupper(Util::camelCaseToUnderscore($attribute));

            if (
                in_array(
                    $seed->getAttributeType($attribute), [
                        Entity::VARCHAR,
                        Entity::TEXT,
                        AttributeType::DATETIME,
                        AttributeType::DATE,
                    ]
                )
            ) {
                $value = '"' . $value . '"';
            }

            $requestPayload .= "    \"" . $attribute . "\": " . $value;

            if ($i < count($attributeList) - 1) {
                $requestPayload .= ",";
            }

            $requestPayload .= "\n";
        }

        $requestPayload .= "}\n```";

        $entity->set('exampleRequestPayload', $requestPayload);
    }

    private function processFormUrl(LeadCapture $entity): void
    {
        $formId = $entity->getFormId();
        $siteUrl = $this->applicationConfig->getSiteUrl();

        if (!$entity->hasFormEnabled() || !$formId) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $entity->set('formUrl', null);

            return;
        }

        $formUrl = "$siteUrl?entryPoint=leadCaptureForm&id=$formId";

        $entity->set('formUrl', $formUrl);
    }
}
