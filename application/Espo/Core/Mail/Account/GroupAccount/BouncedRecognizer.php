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

namespace Espo\Core\Mail\Account\GroupAccount;

use Espo\Core\Mail\Message;
use Espo\Core\Mail\Message\Part;

class BouncedRecognizer
{
    public function isBounced(Message $message): bool
    {
        $from = $message->getHeader('From');
        $contentType = $message->getHeader('Content-Type');

        if (preg_match('/MAILER-DAEMON|POSTMASTER/i', $from ?? '')) {
            return true;
        }

        if (strpos($contentType ?? '', 'multipart/report') === 0) {
            // @todo Check whether ever works.
            $deliveryStatusPart = $this->getDeliveryStatusPart($message);

            if ($deliveryStatusPart) {
                return true;
            }

            $content = $message->getRawContent();

            if (
                strpos($content, 'message/delivery-status') !== false &&
                strpos($content, 'Status: ') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    public function isHard(Message $message): bool
    {
        $content = $message->getRawContent();

        if (preg_match('/permanent[ ]*[error|failure]/', $content)) {
            return true;
        }

        return false;
    }

    public function extractQueueItemId(Message $message): ?string
    {
        $content = $message->getRawContent();

        if (preg_match('/X-Queue-Item-Id: [a-z0-9\-]*/', $content, $m)) {
            /** @var array{string} */
            $arr = preg_split('/X-Queue-Item-Id: /', $m[0], -1, \PREG_SPLIT_NO_EMPTY);

            return $arr[0];
        }

        $to = $message->getHeader('to');

        if (preg_match('/\+bounce-qid-[a-z0-9\-]*/', $to ?? '', $m)) {
            /** @var array{string} */
            $arr = preg_split('/\+bounce-qid-/', $m[0], -1, \PREG_SPLIT_NO_EMPTY);

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
