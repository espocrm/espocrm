<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\Pdf\Dompdf;

use Espo\Core\AclManager;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Utils\Log;
use Espo\Entities\Attachment;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\Attachment\Checker;

/**
 * @internal
 * @todo Move to `Espo\Tools\Pdf\Util` in v10.2.0. Make not internal.
 */
class ImageSourceProvider
{
    public function __construct(
        private EntityManager $entityManager,
        private Checker $checker,
        private FileStorageManager $fileStorageManager,
        private User $user,
        private AclManager $aclManager,
        private Log $log,
    ) {}

    /**
     * No check against 'inline' role intentionally as it is also used for Image fields.
     */
    public function get(string $id, bool $applyAcl = false): ?string
    {
        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

        if (!$attachment) {
            return null;
        }

        try {
            $this->checker->checkTypeImage($attachment);
        } catch (Forbidden) {
            return null;
        }

        if ($applyAcl && !$this->aclManager->checkEntityRead($this->user, $attachment)) {
            $this->log->notice("PDF: User does not have access to the image attachment '{$attachment->getId()}'.");

            return null;
        }

        $type = $attachment->getType();

        if (!$type) {
            return null;
        }

        $contents = $this->fileStorageManager->getContents($attachment);

        return 'data:' . $type . ';base64,' . base64_encode($contents);
    }
}
