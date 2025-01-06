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

namespace Espo\Modules\Crm\Tools\Campaign;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Language;
use Espo\Entities\Template;
use Espo\Modules\Crm\Entities\Campaign as CampaignEntity;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\ORM\Collection;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;

class MailMergeService
{
    /** @var array<string, string[]> */
    protected $entityTypeAddressFieldListMap = [
        'Account' => ['billingAddress', 'shippingAddress'],
        'Contact' => ['address'],
        'Lead' => ['address'],
        'User' => [],
    ];

    /** @var string[] */
    protected $targetLinkList = [
        'accounts',
        'contacts',
        'leads',
        'users',
    ];

    private EntityManager $entityManager;
    private Acl $acl;
    private Language $defaultLanguage;
    private MailMergeGenerator $generator;

    public function __construct(
        EntityManager $entityManager,
        Acl $acl,
        Language $defaultLanguage,
        MailMergeGenerator $generator
    ) {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->defaultLanguage = $defaultLanguage;
        $this->generator = $generator;
    }

    /**
     * @return string An attachment ID.
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     */
    public function generate(string $campaignId, string $link, bool $checkAcl = true): string
    {
        /** @var CampaignEntity $campaign */
        $campaign = $this->entityManager->getEntityById(CampaignEntity::ENTITY_TYPE, $campaignId);

        if ($checkAcl && !$this->acl->checkEntityRead($campaign)) {
            throw new Forbidden();
        }

        /** @var string $targetEntityType */
        $targetEntityType = $campaign->getRelationParam($link, RelationParam::ENTITY);

        if ($checkAcl && !$this->acl->check($targetEntityType, Acl\Table::ACTION_READ)) {
            throw new Forbidden("Could not mail merge campaign because access to target entity type is forbidden.");
        }

        if (!in_array($link, $this->targetLinkList)) {
            throw new BadRequest();
        }

        if ($campaign->getType() !== CampaignEntity::TYPE_MAIL) {
            throw new Error("Could not mail merge campaign not of Mail type.");
        }

        $templateId = $campaign->get($link . 'TemplateId');

        if (!$templateId) {
            throw new Error("Could not mail merge campaign w/o specified template.");
        }

        /** @var ?Template $template */
        $template = $this->entityManager->getEntityById(Template::ENTITY_TYPE, $templateId);

        if (!$template) {
            throw new Error("Template not found.");
        }

        if ($template->getTargetEntityType() !== $targetEntityType) {
            throw new Error("Template is not of proper entity type.");
        }

        $campaign->loadLinkMultipleField('targetLists');
        $campaign->loadLinkMultipleField('excludingTargetLists');

        if (count($campaign->getLinkMultipleIdList('targetLists')) === 0) {
            throw new Error("Could not mail merge campaign w/o any specified target list.");
        }

        $metTargetHash = [];
        $targetEntityList = [];

        /** @var Collection<TargetList> $excludingTargetListList */
        $excludingTargetListList = $this->entityManager
            ->getRDBRepository(CampaignEntity::ENTITY_TYPE)
            ->getRelation($campaign, 'excludingTargetLists')
            ->find();

        foreach ($excludingTargetListList as $excludingTargetList) {
            $recordList = $this->entityManager
                ->getRDBRepository(TargetList::ENTITY_TYPE)
                ->getRelation($excludingTargetList, $link)
                ->find();

            foreach ($recordList as $excludingTarget) {
                $hashId = $excludingTarget->getEntityType() . '-' . $excludingTarget->getId();
                $metTargetHash[$hashId] = true;
            }
        }

        $addressFieldList = $this->entityTypeAddressFieldListMap[$targetEntityType];

        /** @var Collection<TargetList> $targetListCollection */
        $targetListCollection = $this->entityManager
            ->getRDBRepository(CampaignEntity::ENTITY_TYPE)
            ->getRelation($campaign, 'targetLists')
            ->find();

        foreach ($targetListCollection as $targetList) {
            if (!$campaign->get($link . 'TemplateId')) {
                continue;
            }

            $entityList = $this->entityManager
                ->getRDBRepository(TargetList::ENTITY_TYPE)
                ->getRelation($targetList, $link)
                ->where([
                    '@relation.optedOut' => false,
                ])
                ->find();

            foreach ($entityList as $e) {
                $hashId = $e->getEntityType() . '-'. $e->getId();

                if (!empty($metTargetHash[$hashId])) {
                    continue;
                }

                $metTargetHash[$hashId] = true;

                if ($campaign->get('mailMergeOnlyWithAddress')) {
                    if (empty($addressFieldList)) {
                        continue;
                    }

                    $hasAddress = false;

                    foreach ($addressFieldList as $addressField) {
                        if (
                            $e->get($addressField . 'Street') ||
                            $e->get($addressField . 'PostalCode')
                        ) {
                            $hasAddress = true;
                            break;
                        }
                    }

                    if (!$hasAddress) {
                        continue;
                    }
                }

                $targetEntityList[] = $e;
            }
        }

        if (empty($targetEntityList)) {
            throw new Error("No targets available for mail merge.");
        }

        $filename = $campaign->getName() . ' - ' .
            $this->defaultLanguage->translateLabel($targetEntityType, 'scopeNamesPlural');

        /** @var EntityCollection<Entity> $collection */
        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create($targetEntityType, $targetEntityList);

        return $this->generator->generate(
            $collection,
            $template,
            $campaign->getId(),
            $filename
        );
    }
}
