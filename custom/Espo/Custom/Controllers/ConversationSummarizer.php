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

namespace Espo\Custom\Controllers;

use Espo\Core\Acl;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Custom\Services\ConversationSummarizerService;

/**
 * Controller exposing Groq AI Conversation Summarizer REST endpoints.
 */
class ConversationSummarizer
{
    public static string $defaultAction = 'status';

    public function __construct(
        private ConversationSummarizerService $service,
        private Acl $acl
    ) {}

    /**
     * GET /api/v1/ConversationSummarizer/status
     * Returns provider details, model configuration, and readiness status.
     *
     * @return array<string, mixed>
     */
    public function getActionStatus(Request $request): array
    {
        return $this->service->getStatus();
    }

    /**
     * Fallback alias for status action.
     *
     * @return array<string, mixed>
     */
    public function actionStatus(Request $request): array
    {
        return $this->getActionStatus($request);
    }

    /**
     * POST /api/v1/ConversationSummarizer/summarize
     * Generates an executive summary, key points, action items, sentiment, and deal temperature.
     *
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function postActionSummarize(Request $request): array
    {
        $body = $request->getParsedBody();
        $params = is_object($body) ? (array) $body : (is_array($body) ? $body : []);

        if (empty($params)) {
            $rawContent = $request->getBodyContents();
            if ($rawContent) {
                $decoded = json_decode($rawContent, true);
                if (is_array($decoded)) {
                    $params = $decoded;
                }
            }
        }

        return $this->service->summarize($params);
    }

    /**
     * Fallback alias for summarize action.
     *
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function actionSummarize(Request $request): array
    {
        return $this->postActionSummarize($request);
    }
}
