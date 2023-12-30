<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Services;

use Espo\Entities\LeadCapture as LeadCaptureEntity;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;
use Espo\Tools\LeadCapture\Service as LeadCaptureService;
use Espo\Core\Utils\Util;

/**
 * @extends Record<LeadCaptureEntity>
 */
class LeadCapture extends Record
{
    /** @var string[] */
    protected $readOnlyAttributeList = ['apiKey'];

    /**
     * @param LeadCaptureEntity $entity
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $entity->set('exampleRequestMethod', 'POST');

        $entity->set('exampleRequestHeaders', [
            'Content-Type: application/json',
        ]);

        $apiKey = $entity->getApiKey();

        if ($apiKey) {
            $requestUrl = $this->config->getSiteUrl() . '/api/v1/LeadCapture/' . $apiKey;

            $entity->set('exampleRequestUrl', $requestUrl);
        }

        $fieldUtil = $this->fieldUtil;

        $requestPayload = "```{\n";

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
            foreach ($fieldUtil->getActualAttributeList(Lead::ENTITY_TYPE, $field) as $attribute) {
                if (!in_array($attribute, $attributeIgnoreList)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        $seed = $this->entityManager->getNewEntity(Lead::ENTITY_TYPE);

        foreach ($attributeList as $i => $attribute) {
            $value = strtoupper(Util::camelCaseToUnderscore($attribute));

            if (in_array($seed->getAttributeType($attribute), [Entity::VARCHAR, Entity::TEXT])) {
                $value = '"' . $value . '"';
            }

            $requestPayload .= "    \"" . $attribute . "\": " . $value;

            if ($i < count($attributeList) - 1) {
                $requestPayload .= ",";
            }

            $requestPayload .= "\n";
        }

        $requestPayload .= '}```';

        $entity->set('exampleRequestPayload', $requestPayload);
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $apiKey = $this->createLeadCaptureService()->generateApiKey();

        $entity->set('apiKey', $apiKey);
    }

    protected function createLeadCaptureService(): LeadCaptureService
    {
        return $this->injectableFactory->create(LeadCaptureService::class);
    }
}
