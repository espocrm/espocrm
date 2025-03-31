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

namespace Espo\Core\Mail\Account\GroupAccount;

use Espo\Core\Mail\Message;
use Espo\Core\Mail\Message\Part;

use const PREG_SPLIT_NO_EMPTY;

class BouncedRecognizer
{
    /** @var string[] */
    private array $hardBounceCodeList = [
        '5.0.0',
        '5.1.1', // bad destination mailbox address
        '5.1.2', // bad destination system address
        '5.1.6', // destination mailbox has moved, no forwarding address
        '5.4.1', // no answer from host
    ];

    public function isBounced(Message $message): bool
    {
        $from = $message->getHeader('From');
        $contentType = $message->getHeader('Content-Type');

        if (preg_match('/MAILER-DAEMON|POSTMASTER/i', $from ?? '')) {
            return true;
        }

        if (str_starts_with($contentType ?? '', 'multipart/report')) {
            // @todo Check whether ever works.
            $deliveryStatusPart = $this->getDeliveryStatusPart($message);

            if ($deliveryStatusPart) {
                return true;
            }

            $content = $message->getRawContent();

            if (
                str_contains($content, 'message/delivery-status') &&
                str_contains($content, 'Status: ')
            ) {
                return true;
            }
        }

        return false;
    }

    public function isHard(Message $message): bool
    {
        $content = $message->getRawContent();

        /** @noinspection RegExpSimplifiable */
        /** @noinspection RegExpDuplicateCharacterInClass */
        if (preg_match('/permanent[ ]*[error|failure]/', $content)) {
            return true;
        }

        $m = null;

        $has5xxStatus = preg_match('/Status: (5\.[0-9]\.[0-9])/', $content, $m);

        if ($has5xxStatus) {
            $status = $m[1] ?? null;

            if (in_array($status, $this->hardBounceCodeList)) {
                return true;
            }
        }

        return false;
    }

    public function extractStatus(Message $message): ?string
    {
        $content = $message->getRawContent();

        $m = null;

        $hasStatus = preg_match('/Status: ([0-9]\.[0-9]\.[0-9])/', $content, $m);

        if ($hasStatus) {
            return $m[1] ?? null;
        }

        return null;
    }

    public function extractQueueItemId(Message $message): ?string
    {
        $content = $message->getRawContent();

        if (preg_match('/X-Queue-Item-Id: [a-z0-9\-]*/', $content, $m)) {
            /** @var array{string} $arr */
            $arr = preg_split('/X-Queue-Item-Id: /', $m[0], -1, PREG_SPLIT_NO_EMPTY);

            return $arr[0];
        }

        $to = $message->getHeader('to');

        if (preg_match('/\+bounce-qid-[a-z0-9\-]*/', $to ?? '', $m)) {
            /** @var array{string} $arr */
            $arr = preg_split('/\+bounce-qid-/', $m[0], -1, PREG_SPLIT_NO_EMPTY);

            return $arr[0];
        }

        return null;
    }

    private function getDeliveryStatusPart(Message $message): ?Part
    {
        foreach ($message->getPartList() as $part) {
            if ($part->getContentType() === 'message/delivery-status') {
                return $part;
            }
        }

        return null;
    }
}
