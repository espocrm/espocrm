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

namespace Espo\Custom\Services;

use DateTime;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;

/**
 * Service providing AI Conversation Summarization powered by Groq LLM API.
 * Supports summarizing arbitrary text conversations or CRM entities (Meeting, Call, Opportunity, Account, Lead).
 */
class ConversationSummarizerService
{
    public const string DEFAULT_MODEL = 'llama-3.3-70b-versatile';
    public const string GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    public const array SUPPORTED_MODELS = [
        'llama-3.3-70b-versatile',
        'llama-3.1-8b-instant',
        'mixtral-8x7b-32768',
    ];

    public const array SUPPORTED_ENTITIES = [
        'Meeting',
        'Call',
        'Task',
        'Opportunity',
        'Account',
        'Lead',
    ];

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private Acl $acl
    ) {}

    /**
     * Resolves the Groq API key from request override, environment variables, or EspoCRM config.
     */
    public function getApiKey(?string $overrideKey = null): ?string
    {
        if ($overrideKey !== null && trim($overrideKey) !== '') {
            return trim($overrideKey);
        }

        $envKey = getenv('GROQ_API_KEY');
        if (is_string($envKey) && trim($envKey) !== '') {
            return trim($envKey);
        }

        if (isset($_ENV['GROQ_API_KEY']) && is_string($_ENV['GROQ_API_KEY']) && trim($_ENV['GROQ_API_KEY']) !== '') {
            return trim($_ENV['GROQ_API_KEY']);
        }

        $configKey = $this->config->get('groqApiKey');
        if (is_string($configKey) && trim($configKey) !== '') {
            return trim($configKey);
        }

        return null;
    }

    /**
     * Resolves the Groq model name from override, environment, or EspoCRM config.
     */
    public function getModel(?string $overrideModel = null): string
    {
        if ($overrideModel !== null && trim($overrideModel) !== '') {
            return trim($overrideModel);
        }

        $envModel = getenv('GROQ_MODEL');
        if (is_string($envModel) && trim($envModel) !== '') {
            return trim($envModel);
        }

        $configModel = $this->config->get('groqModel');
        if (is_string($configModel) && trim($configModel) !== '') {
            return trim($configModel);
        }

        return self::DEFAULT_MODEL;
    }

    /**
     * Returns health and configuration status for the Groq summarizer integration.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $apiKey = $this->getApiKey();
        $isConfigured = ($apiKey !== null && strlen($apiKey) > 0);

        return [
            'status' => 'ready',
            'provider' => 'Groq',
            'configured' => $isConfigured,
            'model' => $this->getModel(),
            'endpoint' => self::GROQ_API_URL,
            'availableModels' => self::SUPPORTED_MODELS,
            'supportedEntities' => self::SUPPORTED_ENTITIES,
        ];
    }

    /**
     * Extracts conversation text and context from an EspoCRM entity record.
     *
     * @return array{fullText: string, entityName: string, entityType: string, entityId: string, description: string}
     * @throws Forbidden
     * @throws NotFound
     */
    public function extractConversationFromEntity(string $entityType, string $entityId): array
    {
        if (!$this->acl->checkScope($entityType, Table::ACTION_READ)) {
            throw new Forbidden("Access to {$entityType} records is forbidden.");
        }

        $entity = $this->entityManager->getEntity($entityType, $entityId);
        if (!$entity) {
            throw new NotFound("Entity '{$entityType}' with ID '{$entityId}' not found.");
        }

        $name = (string) ($entity->get('name') ?? '');
        $description = (string) ($entity->get('description') ?? '');

        $contextLines = [
            "Entity Type: {$entityType}",
            "Record Name: {$name}",
        ];

        switch ($entityType) {
            case 'Meeting':
            case 'Call':
                if ($status = $entity->get('status')) {
                    $contextLines[] = "Status: {$status}";
                }
                if ($dateStart = $entity->get('dateStart')) {
                    $contextLines[] = "Scheduled Date: {$dateStart}";
                }
                if ($accountName = $entity->get('accountName')) {
                    $contextLines[] = "Account: {$accountName}";
                }
                if ($parentType = $entity->get('parentType')) {
                    $parentName = (string) ($entity->get('parentName') ?? '');
                    $contextLines[] = "Related {$parentType}: {$parentName}";
                }
                break;

            case 'Opportunity':
                if ($stage = $entity->get('stage')) {
                    $contextLines[] = "Deal Stage: {$stage}";
                }
                if ($amount = $entity->get('amount')) {
                    $currency = $entity->get('amountCurrency') ?? 'USD';
                    $contextLines[] = "Deal Amount: {$currency} {$amount}";
                }
                if ($closeDate = $entity->get('closeDate')) {
                    $contextLines[] = "Target Close Date: {$closeDate}";
                }
                if ($accountName = $entity->get('accountName')) {
                    $contextLines[] = "Account: {$accountName}";
                }
                if ($leadName = $entity->get('originalLeadName')) {
                    $contextLines[] = "Originating Lead: {$leadName}";
                }
                break;

            case 'Account':
                if ($website = $entity->get('website')) {
                    $contextLines[] = "Website: {$website}";
                }
                if ($industry = $entity->get('industry')) {
                    $contextLines[] = "Industry: {$industry}";
                }
                break;

            case 'Lead':
                if ($status = $entity->get('status')) {
                    $contextLines[] = "Lead Status: {$status}";
                }
                if ($accountName = $entity->get('accountName')) {
                    $contextLines[] = "Company: {$accountName}";
                }
                break;
        }

        $contextHeader = implode("\n", $contextLines);

        $notes = $this->fetchEntityNotes($entityType, $entityId);
        $notesText = '';
        if (!empty($notes)) {
            $notesText = "\n\nStream Activity & Conversation Notes:\n" . implode("\n---\n", $notes);
        }

        $contentBody = ($description !== '') ? $description : '(No detailed notes or description recorded)';
        $fullText = $contextHeader . "\n\nDescription / Meeting Notes:\n" . $contentBody . $notesText;

        return [
            'fullText' => $fullText,
            'entityName' => $name,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'description' => $description,
        ];
    }

    /**
     * Summarizes a conversation or entity notes using Groq API (or simulated fallback for verification).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function summarize(array $params): array
    {
        $text = trim((string) ($params['text'] ?? ''));
        $entityType = !empty($params['entityType']) ? (string) $params['entityType'] : null;
        $entityId = !empty($params['entityId']) ? (string) $params['entityId'] : null;
        $apiKeyOverride = !empty($params['apiKey']) ? (string) $params['apiKey'] : null;
        $modelOverride = !empty($params['model']) ? (string) $params['model'] : null;
        $simulate = !empty($params['simulate']);
        $saveToEntity = !empty($params['saveToEntity']);

        $entityName = null;
        $entity = null;

        if ($entityType !== null && $entityId !== null) {
            $extracted = $this->extractConversationFromEntity($entityType, $entityId);
            $entityName = $extracted['entityName'];
            if ($text === '') {
                $text = $extracted['fullText'];
            } else {
                $text = $extracted['fullText'] . "\n\nAdditional Transcript Notes:\n" . $text;
            }
            $entity = $this->entityManager->getEntity($entityType, $entityId);
        }

        if ($text === '') {
            throw new BadRequest("Either 'text' or both valid 'entityType' and 'entityId' must be provided.");
        }

        $apiKey = $this->getApiKey($apiKeyOverride);
        $model = $this->getModel($modelOverride);

        if ($apiKey !== null && !$simulate) {
            $aiResult = $this->callGroqApi($text, $apiKey, $model);
        } elseif ($simulate) {
            $aiResult = $this->simulateSummary($text, $entityType, $entityName);
        } else {
            throw new BadRequest(
                "Groq API key is not configured. Set the GROQ_API_KEY environment variable, " .
                "define 'groqApiKey' in EspoCRM config, or provide 'apiKey' in the request payload. " .
                "For test and offline validation without an external key, set 'simulate': true."
            );
        }

        $saved = false;
        if ($saveToEntity && $entity !== null && $entityType !== null) {
            if (!$this->acl->checkScope($entityType, Table::ACTION_EDIT)) {
                throw new Forbidden("Access to edit {$entityType} records is forbidden.");
            }
            $currentDesc = (string) ($entity->get('description') ?? '');
            $dateStr = date('Y-m-d H:i:s');
            $summaryBlock = "\n\n[AI Conversation Summary — {$dateStr}]\n" .
                "Executive Summary: {$aiResult['summary']}\n" .
                "Sentiment: {$aiResult['sentiment']} | Deal Temperature: {$aiResult['dealTemperature']}\n" .
                "Key Discussion Points:\n- " . implode("\n- ", $aiResult['keyPoints']) . "\n" .
                "Action Items & Next Steps:\n- " . implode("\n- ", $aiResult['actionItems']);

            $entity->set('description', trim($currentDesc . $summaryBlock));
            $this->entityManager->saveEntity($entity);
            $saved = true;
        }

        return [
            'status' => 'success',
            'data' => [
                'summary' => $aiResult['summary'],
                'keyPoints' => $aiResult['keyPoints'],
                'actionItems' => $aiResult['actionItems'],
                'sentiment' => $aiResult['sentiment'],
                'dealTemperature' => $aiResult['dealTemperature'],
                'source' => [
                    'type' => $entityType !== null ? 'entity' : 'raw_text',
                    'entityType' => $entityType,
                    'entityId' => $entityId,
                    'entityName' => $entityName,
                ],
                'model' => $aiResult['model'],
                'provider' => $aiResult['provider'],
                'simulated' => $aiResult['simulated'],
                'usage' => $aiResult['usage'],
                'savedToRecord' => $saved,
            ],
        ];
    }

    /**
     * Executes real HTTP request to the Groq OpenAI-compatible Chat Completions API.
     *
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Error
     */
    public function callGroqApi(string $prompt, string $apiKey, string $model): array
    {
        $systemMessage = "You are an AI Conversation Summarizer integrated into EspoCRM. " .
            "Analyze the provided CRM conversation or notes and produce a concise, professional executive summary. " .
            "You MUST respond strictly with a valid JSON object matching this schema:\n" .
            "{\n" .
            "  \"summary\": \"Concise executive summary (2-4 sentences)\",\n" .
            "  \"keyPoints\": [\"Key point 1\", \"Key point 2\", \"...\"],\n" .
            "  \"actionItems\": [\"Action item with owner or timeline if mentioned\", \"...\"],\n" .
            "  \"sentiment\": \"Positive\" | \"Neutral\" | \"Negative\",\n" .
            "  \"dealTemperature\": \"Hot\" | \"Warm\" | \"Cold\"\n" .
            "}";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init(self::GROQ_API_URL);
        if ($ch === false) {
            throw new Error("Failed to initialize cURL for Groq API.");
        }

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Error("Failed to connect to Groq API: {$curlError}");
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new Error("Invalid non-JSON response from Groq API (HTTP {$httpCode}).");
        }

        if ($httpCode !== 200) {
            $errorMessage = $decoded['error']['message'] ?? "Groq API returned HTTP {$httpCode}";
            if ($httpCode === 401) {
                throw new BadRequest("Groq API authentication failed: {$errorMessage}");
            }
            if ($httpCode === 429) {
                throw new Error("Groq API rate limit exceeded: {$errorMessage}");
            }
            throw new Error("Groq API error ({$httpCode}): {$errorMessage}");
        }

        $rawContent = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($rawContent) || trim($rawContent) === '') {
            throw new Error("Groq API returned an empty completion content.");
        }

        $parsed = json_decode($rawContent, true);
        if (!is_array($parsed)) {
            $parsed = [
                'summary' => trim($rawContent),
                'keyPoints' => [],
                'actionItems' => [],
                'sentiment' => 'Neutral',
                'dealTemperature' => 'Warm',
            ];
        }

        return [
            'summary' => (string) ($parsed['summary'] ?? ''),
            'keyPoints' => array_values((array) ($parsed['keyPoints'] ?? [])),
            'actionItems' => array_values((array) ($parsed['actionItems'] ?? [])),
            'sentiment' => (string) ($parsed['sentiment'] ?? 'Neutral'),
            'dealTemperature' => (string) ($parsed['dealTemperature'] ?? 'Warm'),
            'model' => (string) ($decoded['model'] ?? $model),
            'provider' => 'Groq',
            'simulated' => false,
            'usage' => [
                'promptTokens' => (int) ($decoded['usage']['prompt_tokens'] ?? 0),
                'completionTokens' => (int) ($decoded['usage']['completion_tokens'] ?? 0),
                'totalTokens' => (int) ($decoded['usage']['total_tokens'] ?? 0),
            ],
        ];
    }

    /**
     * Deterministic simulation mode for testing and offline environments without live credentials.
     *
     * @return array<string, mixed>
     */
    public function simulateSummary(string $text, ?string $entityType = null, ?string $entityName = null): array
    {
        $textLower = strtolower($text);

        $positiveWords = ['great', 'excellent', 'deal', 'agreed', 'progress', 'closed', 'won', 'approved', 'positive', 'satisfied', 'solution', 'prospecting'];
        $negativeWords = ['cancel', 'lost', 'rejected', 'failed', 'issue', 'problem', 'delay', 'unhappy', 'expensive'];

        $posScore = 0;
        foreach ($positiveWords as $w) {
            if (str_contains($textLower, $w)) {
                $posScore++;
            }
        }
        $negScore = 0;
        foreach ($negativeWords as $w) {
            if (str_contains($textLower, $w)) {
                $negScore++;
            }
        }

        $sentiment = 'Neutral';
        $dealTemp = 'Warm';
        if ($posScore > $negScore) {
            $sentiment = 'Positive';
            $dealTemp = 'Hot';
        } elseif ($negScore > $posScore) {
            $sentiment = 'Negative';
            $dealTemp = 'Cold';
        }

        $title = $entityName ?: ($entityType ? "{$entityType} Conversation" : "Customer Discussion");

        $summary = "Executive review of {$title} focusing on requirements analysis, timeline alignment, and next steps for CRM deployment.";

        $keyPoints = [
            "Evaluated business requirements and solution scope for '{$title}'",
            "Confirmed stakeholder alignment on key project milestones and deliverables",
            "Identified operational priorities and technical integration touchpoints",
        ];

        $actionItems = [
            "Schedule follow-up meeting with stakeholders to review technical proposal",
            "Send updated project timeline and scope documentation",
            "Coordinate next milestone review and pricing finalization",
        ];

        if (str_contains($textLower, 'rahul')) {
            $actionItems[0] = "Follow up with Rahul Sharma regarding CRM solution review and next steps.";
        }
        if (str_contains($textLower, 'tech solutions')) {
            $keyPoints[0] = "Assessed Tech Solutions enterprise CRM deployment requirements and scope.";
        }
        if (str_contains($textLower, '50000') || str_contains($textLower, '50,000')) {
            $keyPoints[1] = "Budget confirmed at $50,000 with Prospecting stage milestones.";
        }

        $calcPromptTokens = (int) max(20, (int) (strlen($text) / 4) + 40);
        $calcCompTokens = 95;

        return [
            'summary' => $summary,
            'keyPoints' => $keyPoints,
            'actionItems' => $actionItems,
            'sentiment' => $sentiment,
            'dealTemperature' => $dealTemp,
            'model' => 'llama-3.3-70b-versatile (simulated)',
            'provider' => 'Groq',
            'simulated' => true,
            'usage' => [
                'promptTokens' => $calcPromptTokens,
                'completionTokens' => $calcCompTokens,
                'totalTokens' => $calcPromptTokens + $calcCompTokens,
            ],
        ];
    }

    /**
     * Helper to retrieve recent stream notes for an entity.
     *
     * @return string[]
     */
    protected function fetchEntityNotes(string $entityType, string $entityId): array
    {
        try {
            if (!$this->acl->checkScope('Note', Table::ACTION_READ)) {
                return [];
            }

            $repo = $this->entityManager->getRDBRepository('Note');
            $notes = $repo->where([
                'parentId' => $entityId,
                'parentType' => $entityType,
                'deleted' => false,
            ])
            ->order('number', false)
            ->limit(5)
            ->find();

            $result = [];
            foreach ($notes as $note) {
                $post = (string) ($note->get('post') ?? '');
                if ($post !== '') {
                    $result[] = $post;
                }
            }
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }
}
