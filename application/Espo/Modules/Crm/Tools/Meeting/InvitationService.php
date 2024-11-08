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

namespace Espo\Modules\Crm\Tools\Meeting;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\Crm\Business\Event\Invitations;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Tools\Email\SendService;

class InvitationService
{
    private const TYPE_INVITATION = 'invitation';
    private const TYPE_CANCELLATION = 'cancellation';

    public function __construct(
        private RecordServiceContainer $recordServiceContainer,
        private SendService $sendService,
        private User $user,
        private InjectableFactory $injectableFactory,
        private Acl $acl,
        private EntityManager $entityManager,
        private Config $config,
        private Metadata $metadata
    ) {}

    /**
     * Send invitation emails for a meeting (or call). Checks access. Uses user's SMTP if available.
     *
     * @param ?Invitee[] $targets
     * @return Entity[] Entities an invitation was sent to.
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    public function send(string $entityType, string $id, ?array $targets = null): array
    {
        return $this->sendInternal($entityType, $id, $targets);
    }

    /**
     * Send cancellation emails for a meeting (or call). Checks access. Uses user's SMTP if available.
     *
     * @param ?Invitee[] $targets
     * @return Entity[] Entities a cancellation was sent to.
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    public function sendCancellation(string $entityType, string $id, ?array $targets = null): array
    {
        return $this->sendInternal($entityType, $id, $targets, self::TYPE_CANCELLATION);
    }

    /**
     * @param ?Invitee[] $targets
     * @return Entity[]
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    private function sendInternal(
        string $entityType,
        string $id,
        ?array $targets = null,
        string $type = self::TYPE_INVITATION
    ): array {

        $entity = $this->recordServiceContainer
            ->get($entityType)
            ->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden("No edit access.");
        }

        $this->checkStatus($entity);

        $linkList = [
            'users',
            'contacts',
            'leads',
        ];

        $sender = $this->getSender();

        $sentAddressList = [];
        $resultEntityList = [];

        foreach ($linkList as $link) {
            $builder = $this->entityManager
                ->getRDBRepository($entityType)
                ->getRelation($entity, $link);

            if ($targets === null && $type === self::TYPE_INVITATION) {
                $builder->where([
                    '@relation.status=' => Meeting::ATTENDEE_STATUS_NONE,
                ]);
            }

            $collection = $builder->find();

            foreach ($collection as $attendee) {
                if ($targets && !self::isInTargets($attendee, $targets)) {
                    continue;
                }

                $emailAddress = $attendee->get('emailAddress');

                if (!$emailAddress || in_array($emailAddress, $sentAddressList)) {
                    continue;
                }

                if ($type === self::TYPE_INVITATION) {
                    $sender->sendInvitation($entity, $attendee, $link);
                }

                if ($type === self::TYPE_CANCELLATION) {
                    $sender->sendCancellation($entity, $attendee, $link);
                }

                $sentAddressList[] = $emailAddress;
                $resultEntityList[] = $attendee;

                $this->entityManager
                    ->getRDBRepository($entityType)
                    ->getRelation($entity, $link)
                    ->updateColumns($attendee, ['status' => Meeting::ATTENDEE_STATUS_NONE]);
            }
        }

        return $resultEntityList;
    }

    /**
     * @param Invitee[] $targets
     */
    private static function isInTargets(Entity $entity, array $targets): bool
    {
        foreach ($targets as $target) {
            if (
                $entity->getEntityType() === $target->getEntityType() &&
                $entity->getId() === $target->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    private function getSender(): Invitations
    {
        $smtpParams = !$this->config->get('eventInvitationForceSystemSmtp') ?
            $this->sendService->getUserSmtpParams($this->user->getId()) :
            null;

        $builder = BindingContainerBuilder::create();

        if ($smtpParams) {
            $builder->bindInstance(SmtpParams::class, $smtpParams);
        }

        return $this->injectableFactory->createWithBinding(Invitations::class, $builder->build());
    }

    /**
     * @throws Forbidden
     */
    private function checkStatus(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        $notActualStatusList = [
            ...($this->metadata->get("scopes.$entityType.completedStatusList") ?? []),
            ...($this->metadata->get("scopes.$entityType.canceledStatusList") ?? []),
        ];

        if (in_array($entity->get('status'), $notActualStatusList)) {
            throw new Forbidden("Can't send invitation for not actual event.");
        }
    }
}
