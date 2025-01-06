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

namespace Espo\Tools\Email;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FileStorage\Manager;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\Importer\Data;
use Espo\Core\Mail\MessageWrapper;
use Espo\Core\Mail\Parsers\MailMimeParser;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;

class ImportEmlService
{
    public function __construct(
        private Importer $importer,
        private Importer\DuplicateFinder $duplicateFinder,
        private EntityManager $entityManager,
        private Manager $fileStorageManager,
        private MailMimeParser $parser,
    ) {}

    /**
     * Import an EML.
     *
     * @param string $fileId An attachment ID.
     * @param ?string $userId A user ID to relate an email with.
     * @return Email An Email.
     * @throws NotFound
     * @throws Error
     * @throws Conflict
     */
    public function import(string $fileId, ?string $userId = null): Email
    {
        $attachment = $this->getAttachment($fileId);
        $contents = $this->fileStorageManager->getContents($attachment);

        $message = new MessageWrapper(1, null, $this->parser, $contents);

        $this->checkDuplicate($message);

        $email = $this->importer->import($message, Data::create());

        if (!$email) {
            throw new Error("Could not import.");
        }

        if ($userId) {
            $this->entityManager->getRDBRepositoryByClass(Email::class)
                ->getRelation($email, 'users')
                ->relateById($userId);
        }

        $this->entityManager->removeEntity($attachment);

        return $email;
    }

    /**
     * @throws NotFound
     */
    private function getAttachment(string $fileId): Attachment
    {
        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($fileId);

        if (!$attachment) {
            throw new NotFound("Attachment not found.");
        }

        return $attachment;
    }

    /**
     * @throws Conflict
     */
    private function checkDuplicate(MessageWrapper $message): void
    {
        $messageId = $this->parser->getMessageId($message);

        if (!$messageId) {
            return;
        }

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();
        $email->setMessageId($messageId);

        $duplicate = $this->duplicateFinder->find($email, $message);

        if (!$duplicate) {
            return;
        }

        throw Conflict::createWithBody(
            'Email is already imported.',
            Error\Body::create()->withMessageTranslation('alreadyImported', Email::ENTITY_TYPE, [
                'id' => $duplicate->getId(),
                'link' => '#Email/view/' . $duplicate->getId(),
            ])
        );
    }
}
