<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\BadRequest;

use \Espo\ORM\Entity;

class MassEmail extends \Espo\Services\Record
{
    const MAX_ATTEMPT_COUNT = 3;

    const MAX_PER_HOUR_COUNT = 10000;

    private $campaignService = null;

    private $emailTemplateService = null;

    protected $mandatorySelectAttributeList = ['campaignId'];

    protected function init()
    {
        parent::init();
        $this->addDependency('container');
        $this->addDependency('defaultLanguage');
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getLanguage()
    {
        return $this->getInjection('defaultLanguage');
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }

    protected function afterDeleteEntity(Entity $massEmail)
    {
        parent::afterDeleteEntity($massEmail);
        $existingQueueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => ['Pending', 'Failed'],
            'massEmailId' => $massEmail->id
        ))->find();
        foreach ($existingQueueItemList as $existingQueueItem) {
            $this->getEntityManager()->getMapper('RDB')->deleteFromDb('EmailQueueItem', $existingQueueItem->id);
        }
    }

    public function createQueue(Entity $massEmail, $isTest = false, $additionalTargetList = [])
    {
        if (!$isTest && $massEmail->get('status') !== 'Pending') {
            throw new Error("Mass Email '".$massEmail->id."' should be 'Pending'.");
        }

        if (!$isTest) {
            $existingQueueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
                'status' => ['Pending', 'Failed'],
                'massEmailId' => $massEmail->id
            ))->find();
            foreach ($existingQueueItemList as $existingQueueItem) {
                $this->getEntityManager()->getMapper('RDB')->deleteFromDb('EmailQueueItem', $existingQueueItem->id);
            }
        }

        $metTargetHash = array();
        $metEmailAddressHash = array();
        $entityList = [];

        $pdo = $this->getEntityManager()->getPDO();

        if (!$isTest) {
            $excludingTargetListList = $massEmail->get('excludingTargetLists');
            foreach ($excludingTargetListList as $excludingTargetList) {
                foreach (['accounts', 'contacts', 'leads', 'users'] as $link) {
                    foreach ($excludingTargetList->get($link) as $excludingTarget) {
                        $hashId = $excludingTarget->getEntityType() . '-'. $excludingTarget->id;
                        $metTargetHash[$hashId] = true;
                        $emailAddress = $excludingTarget->get('emailAddress');
                        if ($emailAddress) {
                            $metEmailAddressHash[$emailAddress] = true;
                        }
                    }
                }
            }

            $targetListCollection = $massEmail->get('targetLists');
            foreach ($targetListCollection as $targetList) {
                $accountList = $targetList->get('accounts', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($accountList as $account) {
                    $hashId = $account->getEntityType() . '-'. $account->id;
                    $emailAddress = $account->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $account;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }

                $contactList = $targetList->get('contacts', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($contactList as $contact) {
                    $hashId = $contact->getEntityType() . '-'. $contact->id;
                    $emailAddress = $contact->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $contact;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }

                $leadList = $targetList->get('leads', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($leadList as $lead) {
                    $hashId = $lead->getEntityType() . '-'. $lead->id;
                    $emailAddress = $lead->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $lead;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }

                $userList = $targetList->get('users', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($userList as $user) {
                    $hashId = $user->getEntityType() . '-'. $user->id;
                    $emailAddress = $user->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $user;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }
            }
        }

        foreach ($additionalTargetList as $target) {
            $entityList[] = $target;
        }

        foreach ($entityList as $target) {
            $emailAddress = $target->get('emailAddress');
            if (!$target->get('emailAddress')) continue;
            if (strpos($emailAddress, 'ERASED:') === 0) continue;
            $emailAddressRecord = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($emailAddress);
            if ($emailAddressRecord) {
                if ($emailAddressRecord->get('invalid') || $emailAddressRecord->get('optOut')) {
                    continue;
                }
            }

            $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem');
            $queueItem->set(array(
                'massEmailId' => $massEmail->id,
                'status' => 'Pending',
                'targetId' => $target->id,
                'targetType' => $target->getEntityType(),
                'isTest' => $isTest
            ));
            $this->getEntityManager()->saveEntity($queueItem);
        }

        if (!$isTest) {
            $massEmail->set('status', 'In Process');
            if (empty($entityList)) {
                $massEmail->set('status', 'Complete');
            }

            $this->getEntityManager()->saveEntity($massEmail);
        }
    }

    protected function setFailed(Entity $massEmail)
    {
        $massEmail->set('status', 'Failed');
        $this->getEntityManager()->saveEntity($massEmail);

        $queueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id
        ))->find();
        foreach ($queueItemList as $queueItem) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
        }
    }

    public function processSending(Entity $massEmail, $isTest = false)
    {
        $maxBatchSize = $this->getConfig()->get('massEmailMaxPerHourCount', self::MAX_PER_HOUR_COUNT);

        if (!$isTest) {
            $threshold = new \DateTime();
            $threshold->modify('-1 hour');

            $sentLastHourCount = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
                'status' => 'Sent',
                'sentAt>' => $threshold->format('Y-m-d H:i:s')
            ))->count();

            if ($sentLastHourCount >= $maxBatchSize) {
                return;
            }

            $maxBatchSize = $maxBatchSize - $sentLastHourCount;
        }

        $queueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id,
            'isTest' => $isTest
        ))->limit(0, $maxBatchSize)->find();

        $templateId = $massEmail->get('emailTemplateId');
        if (!$templateId) {
            $this->setFailed($massEmail);
            return;
        }

        $campaign = null;
        $campaignId = $massEmail->get('campaignId');
        if ($campaignId) {
            $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
        }
        $emailTemplate = $this->getEntityManager()->getEntity('EmailTemplate', $templateId);
        if (!$emailTemplate) {
            $this->setFailed($massEmail);
            return;
        }
        $attachmentList = $emailTemplate->get('attachments');

        $smtpParams = null;

        if ($massEmail->get('inboundEmailId')) {
            $inboundEmail = $this->getEntityManager()->getEntity('InboundEmail', $massEmail->get('inboundEmailId'));
            if (!$inboundEmail) {
                throw new Error("Group Email Account '".$massEmail->get('inboundEmailId')."' is not available.");
            }

            if (
                $inboundEmail->get('status') !== 'Active'
                ||
                !$inboundEmail->get('useSmtp')
                ||
                !$inboundEmail->get('smtpIsForMassEmail')
            ) {
                throw new Error("Group Email Account '".$massEmail->get('inboundEmailId')."' can't be used for Mass Email.");
            }

            $inboundEmailService = $this->getServiceFactory()->create('InboundEmail');
            $smtpParams = $inboundEmailService->getSmtpParamsFromAccount($inboundEmail);
            if (!$smtpParams) {
                throw new Error("Group Email Account '".$massEmail->get('inboundEmailId')."' has no SMTP params.");
            }
        }

        foreach ($queueItemList as $queueItem) {
            $this->sendQueueItem($queueItem, $massEmail, $emailTemplate, $attachmentList, $campaign, $isTest, $smtpParams);
        }

        if (!$isTest) {
            $countLeft = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
                'status' => 'Pending',
                'massEmailId' => $massEmail->id,
                'isTest' => false
            ))->count();

            if ($countLeft == 0) {
                $massEmail->set('status', 'Complete');
                $this->getEntityManager()->saveEntity($massEmail);
            }
        }
    }

    protected function getPreparedEmail(Entity $queueItem, Entity $massEmail, Entity $emailTemplate, Entity $target, $trackingUrlList = [])
    {
        $templateParams = array(
            'parent' => $target
        );

        $emailData = $this->getEmailTemplateService()->parseTemplate($emailTemplate, $templateParams);

        $body = $emailData['body'];

        $optOutUrl = $this->getConfig()->get('siteUrl') . '?entryPoint=unsubscribe&id=' . $queueItem->id;
        $optOutLink = '<a href="'.$optOutUrl.'">'.$this->getLanguage()->translate('Unsubscribe', 'labels', 'Campaign').'</a>';

        $body = str_replace('{optOutUrl}', $optOutUrl, $body);
        $body = str_replace('{optOutLink}', $optOutLink, $body);

        foreach ($trackingUrlList as $trackingUrl) {
            $url = $this->getConfig()->get('siteUrl') . '?entryPoint=campaignUrl&id=' . $trackingUrl->id . '&queueItemId=' . $queueItem->id;
            $body = str_replace($trackingUrl->get('urlToUse'), $url, $body);
        }

        if (!$this->getConfig()->get('massEmailDisableMandatoryOptOutLink') && stripos($body, '?entryPoint=unsubscribe&id') === false) {
            if ($emailData['isHtml']) {
                $body .= "<br><br>" . $optOutLink;
            } else {
                $body .= "\n\n" . $optOutUrl;
            }
        }

        $trackImageAlt = $this->getLanguage()->translate('Campaign', 'scopeNames');

        $trackOpenedUrl = $this->getConfig()->get('siteUrl') . '?entryPoint=campaignTrackOpened&id=' . $queueItem->id;
        $trackOpenedHtml = '<img alt="'.$trackImageAlt.'" width="1" height="1" border="0" src="'.$trackOpenedUrl.'">';

        if ($massEmail->get('campaignId') && $this->getConfig()->get('massEmailOpenTracking')) {
            if ($emailData['isHtml']) {
                $body .= '<br>' . $trackOpenedHtml;
            }
        }

        $emailData['body'] = $body;

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set($emailData);

        $emailAddress = $target->get('emailAddress');

        if (empty($emailAddress)) {
            return false;
        }

        $email->set('to', $emailAddress);

        if ($massEmail->get('fromAddress')) {
            $email->set('from', $massEmail->get('fromAddress'));
        }
        if ($massEmail->get('replyToAddress')) {
            $email->set('replyTo', $massEmail->get('replyToAddress'));
        }

        return $email;
    }

    protected function sendQueueItem(Entity $queueItem, Entity $massEmail, Entity $emailTemplate, $attachmentList = [], $campaign = null, $isTest = false, $smtpParams = false)
    {
        $queueItemFetched = $this->getEntityManager()->getEntity($queueItem->getEntityType(), $queueItem->id);
        if ($queueItemFetched->get('status') !== 'Pending') {
            return;
        }

        $queueItem->set('status', 'Sending');
        $this->getEntityManager()->saveEntity($queueItem);

        $target = $this->getEntityManager()->getEntity($queueItem->get('targetType'), $queueItem->get('targetId'));
        if (!$target || !$target->id || !$target->get('emailAddress')) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
            return;
        }

        $emailAddress = $target->get('emailAddress');
        if (!$emailAddress) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
            return false;
        }

        $emailAddressRecord = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($emailAddress);
        if ($emailAddressRecord) {
            if ($emailAddressRecord->get('invalid') || $emailAddressRecord->get('optOut')) {
                $queueItem->set('status', 'Failed');
                $this->getEntityManager()->saveEntity($queueItem);
                return false;
            }
        }

        $trackingUrlList = [];
        if ($campaign) {
            $trackingUrlList = $campaign->get('trackingUrls');
        }

        $email = $this->getPreparedEmail($queueItem, $massEmail, $emailTemplate, $target, $trackingUrlList);

        $params = array();
        if ($massEmail->get('fromName')) {
            $params['fromName'] = $massEmail->get('fromName');
        }
        if ($massEmail->get('replyToName')) {
            $params['replyToName'] = $massEmail->get('replyToName');
        }

        $isSent = false;

        try {
            $attemptCount = $queueItem->get('attemptCount');
            $attemptCount++;
            $queueItem->set('attemptCount', $attemptCount);

            $message = new \Zend\Mail\Message();

            $header = new \Espo\Core\Mail\Mail\Header\XQueueItemId();
            $header->setId($queueItem->id);
            $message->getHeaders()->addHeader($header);

            $message->getHeaders()->addHeaderLine('Precedence', 'bulk');

            if (!$this->getConfig()->get('massEmailDisableMandatoryOptOutLink')) {
                $optOutUrl = $this->getConfig()->getSiteUrl() . '?entryPoint=unsubscribe&id=' . $queueItem->id;
                $message->getHeaders()->addHeaderLine('List-Unsubscribe', '<' . $optOutUrl . '>');
            }

            $sender = $this->getMailSender();

            if ($smtpParams) {
                $sender->useSmtp($smtpParams);
            } else {
                $sender->useGlobal();
            }

            $sender->send($email, $params, $message, $attachmentList);

            $isSent = true;
        } catch (\Exception $e) {
            $maxAttemptCount = $this->getConfig()->get('massEmailMaxAttemptCount', self::MAX_ATTEMPT_COUNT);
            if ($queueItem->get('attemptCount') >= $maxAttemptCount) {
                $queueItem->set('status', 'Failed');
            } else {
                $queueItem->set('status', 'Pending');
            }
            $this->getEntityManager()->saveEntity($queueItem);
            $GLOBALS['log']->error('MassEmail#sendQueueItem: [' . $e->getCode() . '] ' .$e->getMessage());
            return false;
        }

        if ($isSent) {
            $emailObject = $emailTemplate;
            if ($massEmail->get('storeSentEmails') && !$isTest) {
                $this->getEntityManager()->saveEntity($email);
                $emailObject = $email;
            }

            $queueItem->set('emailAddress', $target->get('emailAddress'));

            $queueItem->set('status', 'Sent');
            $queueItem->set('sentAt', date('Y-m-d H:i:s'));
            $this->getEntityManager()->saveEntity($queueItem);

            if ($campaign) {
                $this->getCampaignService()->logSent($campaign->id, $queueItem->id, $target, $emailObject, $target->get('emailAddress'), null, $queueItem->get('isTest'));
            }
        }

        return true;
    }

    protected function getEmailTemplateService()
    {
        if (!$this->emailTemplateService) {
            $this->emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');
        }
        return $this->emailTemplateService;
    }

    protected function getCampaignService()
    {
        if (!$this->campaignService) {
            $this->campaignService = $this->getServiceFactory()->create('Campaign');
        }
        return $this->campaignService;
    }

    protected function findLinkedQueueItems($id, $params)
    {
        $link = 'queueItems';

        $entity = $this->getEntityManager()->getEntity('MassEmail', $id);

        $selectParams = $this->getSelectManager('EmailQueueItem')->getSelectParams($params, false);

        if (array_key_exists($link, $this->linkSelectParams)) {
            $selectParams = array_merge($selectParams, $this->linkSelectParams[$link]);
        }

        $selectParams['whereClause'][] = array(
            'isTest' => false
        );

        $collection = $this->getRepository()->findRelated($entity, $link, $selectParams);

        $recordService = $this->getRecordService('EmailQueueItem');

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);
            $recordService->prepareEntityForOutput($e);
        }

        $total = $this->getRepository()->countRelated($entity, $link, $selectParams);

        return array(
            'total' => $total,
            'collection' => $collection
        );
    }

    public function getSmtpAccountDataList()
    {
        if (!$this->getAcl()->checkScope('MassEmail', 'create') && !$this->getAcl()->checkScope('MassEmail', 'edit')) {
            throw new Forbidden();
        }
        $dataList = [];

        $inboundEmailList = $this->getEntityManager()->getRepository('InboundEmail')->where([
            'useSmtp' => true,
            'status' => 'Active',
            'smtpIsForMassEmail' => true,
            ['emailAddress!=' => ''],
            ['emailAddress!=' => null],
        ])->find();

        foreach ($inboundEmailList as $inboundEmail) {
            $item = (object) [];
            $key = 'inboundEmail:' . $inboundEmail->id;
            $item->key = $key;
            $item->emailAddress = $inboundEmail->get('emailAddress');
            $item->fromName = $inboundEmail->get('fromName');

            $dataList[] = $item;
        }

        return $dataList;
    }

}
