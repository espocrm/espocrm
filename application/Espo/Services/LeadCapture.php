<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Entities\LeadCapture as LeadCaptureEntity;

use Espo\Entities\InboundEmail;

use Espo\{
    Modules\Crm\Entities\Lead,
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

/**
 * @extends Record<\Espo\Entities\LeadCapture>
 */
class LeadCapture extends Record
{
    /**
     * @var string[]
     */
    protected $readOnlyAttributeList = ['apiKey'];

    /**
     * @param LeadCaptureEntity $entity
     * @throws Error
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $entity->set('exampleRequestMethod', 'POST');

        $entity->set('exampleRequestHeaders', [
            'Content-Type: application/json',
        ]);

        $apiKey = $entity->getApiKey();

        if (!$apiKey) {
            throw new Error("No api key.");
        }

        $requestUrl = $this->config->getSiteUrl() . '/api/v1/LeadCapture/' . $apiKey;

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

    /**
     * @throws \Espo\Core\Exceptions\ForbiddenSilent
     * @throws NotFound
     * @throws Error
     */
    public function generateNewApiKeyForEntity(string $id): Entity
    {
        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $apiKey = $this->generateApiKey();

        $entity->set('apiKey', $apiKey);

        $this->entityManager->saveEntity($entity);

        $this->prepareEntityForOutput($entity);

        return $entity;
    }

    public function generateApiKey(): string
    {
        return Util::generateApiKey();
    }

    public function isApiKeyValid(string $apiKey): bool
    {
        $leadCapture = $this->entityManager
            ->getRDBRepository(LeadCaptureEntity::ENTITY_TYPE)
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

    /**
     * @throws \Espo\Core\Exceptions\BadRequest
     * @throws NotFound
     * @throws Error
     */
    public function leadCapture(string $apiKey, stdClass $data): void
    {
        $this->createTool()->capture($apiKey, $data);
    }

    /**
     * @throws Error
     */
    public function jobOptInConfirmation(stdClass $data): void
    {
        if (empty($data->id)) {
            throw new Error();
        }

        $this->createTool()->sendOptInConfirmation($data->id);
    }

    /**
     * @throws \Espo\Core\Exceptions\BadRequest
     * @throws Error
     * @throws NotFound
     *
     * @return array{
     *   status: 'success'|'expired',
     *   message: ?string,
     *   leadCaptureName?: ?string,
     *   leadCaptureId?: string,
     * }
     */
    public function confirmOptIn(string $id): array
    {
        return $this->createTool()->confirmOptIn($id);
    }

    /**
     * @return stdClass[]
     * @throws Forbidden
     */
    public function getSmtpAccountDataList(): array
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $dataList = [];

        $inboundEmailList = $this->entityManager
            ->getRDBRepository(InboundEmail::ENTITY_TYPE)
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
            $item->emailAddress = $inboundEmail->getEmailAddress();
            $item->fromName = $inboundEmail->getFromName();

            $dataList[] = $item;
        }

        return $dataList;
    }
}
