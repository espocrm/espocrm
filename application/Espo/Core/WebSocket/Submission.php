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

namespace Espo\Core\WebSocket;

use Espo\Core\Utils\Log;
use Espo\Core\Utils\Json;

use stdClass;
use Throwable;

class Submission
{
    public function __construct(
        private Sender $sender,
        private Log $log,
        private ConfigDataProvider $configDataProvider,
    ) {}

    /**
     * Submit to a web-socket server.
     *
     * Since 9.1.0 performs check whether enabled in the config.
     *
     * @param stdClass|array<string, mixed>|null $data Data to submit. Assoc array is supported since 9.1.0.
     */
    public function submit(string $topic, ?string $userId = null, stdClass|array|null $data = null): void
    {
        if (!$this->configDataProvider->isEnabled()) {
            return;
        }

        if (!$data) {
            $data = (object) [];
        }

        if (is_array($data)) {
            $data = (object) $data;
        }

        if ($userId) {
            $data->userId = $userId;
        }

        $data->topicId = $topic;

        $message = Json::encode($data);

        try {
            $this->sender->send($message);
        } catch (Throwable $e) {
            $this->log->error("WebSocketSubmission: " . $e->getMessage());
        }
    }
}
