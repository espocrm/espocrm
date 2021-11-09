<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Services;

use Espo\{
    ORM\Entity,
    Tools\LeadCapture\LeadCapture as Tool,
};

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\NotFound,
    Exceptions\Error,
    Utils\Util,
};

use stdClass;

class LeadCapture extends Record
{
    protected $readOnlyAttributeList = ['apiKey'];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $entity->set('exampleRequestMethod', 'POST');

        $requestUrl = $this->getConfig()->getSiteUrl() . '/api/v1/LeadCapture/' . $entity->get('apiKey');

        $entity->set('exampleRequestUrl', $requestUrl);

        $fieldUtil = $this->fieldUtil;

        $requestPayload = "```{\n";

        $attributeList = [];

        $attributeIgnoreList = [
            'emailAddressIsOptedOut',
            'phoneNumberIsOptedOut',
            'emailAddressData',
            'phoneNumberData',
        ];

        $fieldList = $entity->get('fieldList');

        if (is_array($fieldList)) {
            foreach ($fieldList as $field) {
                foreach ($fieldUtil->getActualAttributeList('Lead', $field) as $attribute) {
                    if (!in_array($attribute, $attributeIgnoreList)) {
                        $attributeList[] = $attribute;
                    }
                }
            }
        }

        $seed = $this->getEntityManager()->getEntity('Lead');

        foreach ($attributeList as $i => $attribute) {
            $value = strtoupper(Util::camelCaseToUnderscore($attribute));

            if (
                in_array(
                    $seed->getAttributeType($attribute),
                    [Entity::VARCHAR, Entity::TEXT]
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

        $requestPayload .= '}```';

        $entity->set('exampleRequestPayload', $requestPayload);
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $apiKey = $this->generateApiKey();

        $entity->set('apiKey', $apiKey);
    }

    public function generateNewApiKeyForEntity(string $id): Entity
    {
        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $apiKey = $this->generateApiKey();

        $entity->set('apiKey', $apiKey);

        $this->getEntityManager()->saveEntity($entity);

        $this->prepareEntityForOutput($entity);

        return $entity;
    }

    public function generateApiKey(): string
    {
        return Util::generateApiKey();
    }

    public function isApiKeyValid(string $apiKey): bool
    {
        $leadCapture = $this->getEntityManager()
            ->getRDBRepository('LeadCapture')
            ->where([
                'apiKey' => $apiKey,
                'isActive' => true,
            ])
            ->findOne();

        if ($leadCapture) {
            return true;
        }

        return false;
    }

    protected function createTool(): Tool
    {
        return $this->injectableFactory->create(Tool::class);
    }

    public function leadCapture(string $apiKey, stdClass $data)
    {
        $this->createTool()->capture($apiKey, $data);
    }

    public function jobOptInConfirmation(stdClass $data)
    {
        if (empty($data->id)) {
            throw new Error();
        }

        $this->createTool()->sendOptInConfirmation($data->id);
    }

    public function confirmOptIn(string $id): stdClass
    {
        return $this->createTool()->confirmOptIn($id);
    }

    public function getSmtpAccountDataList(): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        $dataList = [];

        $inboundEmailList = $this->getEntityManager()
            ->getRDBRepository('InboundEmail')
            ->where([
                'useSmtp' => true,
                'status' => 'Active',
                ['emailAddress!=' => ''],
                ['emailAddress!=' => null],
            ])
            ->find();

        foreach ($inboundEmailList as $inboundEmail) {
            $item = (object) [];

            $key = 'inboundEmail:' . $inboundEmail->getId();

            $item->key = $key;
            $item->emailAddress = $inboundEmail->get('emailAddress');
            $item->fromName = $inboundEmail->get('fromName');

            $dataList[] = $item;
        }

        return $dataList;
    }
}
