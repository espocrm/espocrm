<?php
/************************************************************************
 * Verification script for ConversationSummarizerService (W5D5 Unit Tests)
 ************************************************************************/

// Autoloader for Espo and Espo\Custom
spl_autoload_register(function (string $class): void {
    $classPath = str_replace('\\', '/', $class);

    if (str_starts_with($classPath, 'Espo/Custom/')) {
        $file = __DIR__ . '/../../custom/Espo/Custom/' . substr($classPath, 12) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    if (str_starts_with($classPath, 'Espo/')) {
        $file = __DIR__ . '/../../application/Espo/' . substr($classPath, 5) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

use Espo\Custom\Services\ConversationSummarizerService;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Entity as OrmEntity;

echo "=== W5D5: AI Conversation Summarizer Service Unit Verification ===\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition(string $description, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passCount++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $failCount++;
    }
}

// ---------------------------------------------------------
// Mock Helpers
// ---------------------------------------------------------

class MockEntity extends CoreEntity {
    private array $customAttributes = [];

    public function __construct(string $entityType, array $attributes = []) {
        parent::__construct($entityType, []);
        $this->customAttributes = $attributes;
    }

    public function get(string $name): mixed {
        return $this->customAttributes[$name] ?? parent::get($name);
    }

    public function set($attribute, $value = null): static {
        if (is_string($attribute)) {
            $this->customAttributes[$attribute] = $value;
        }
        return $this;
    }

    public function has(string $name): bool {
        return array_key_exists($name, $this->customAttributes) || parent::has($name);
    }
}

class MockConfig extends Config {
    public function __construct(private array $values = []) {}

    public function get(string $key, mixed $default = null): mixed {
        return $this->values[$key] ?? $default;
    }

    public function set($name, $value = null, bool $dontMarkDirty = false): void {
        if (is_string($name)) {
            $this->values[$name] = $value;
        }
    }
}

class MockAcl extends Acl {
    private array $scopePermissions = [];

    public function __construct(array $scopePermissions = []) {
        $this->scopePermissions = $scopePermissions;
    }

    public function checkScope(string $scope, ?string $action = null): bool {
        if ($action !== null && isset($this->scopePermissions[$scope][$action])) {
            return (bool) $this->scopePermissions[$scope][$action];
        }
        return true;
    }
}

class MockNoteRepository {
    private array $notes = [];

    public function __construct(array $notes = []) {
        $this->notes = $notes;
    }

    public function where(array $where): static {
        return $this;
    }

    public function order(string $field, bool $asc = true): static {
        return $this;
    }

    public function limit(int $limit): static {
        return $this;
    }

    public function find(): array {
        return $this->notes;
    }
}

class MockEntityManager extends EntityManager {
    private array $entities = [];
    public array $savedEntities = [];
    public array $notes = [];

    public function __construct(array $entities = [], array $notes = []) {
        $this->entities = $entities;
        $this->notes = $notes;
    }

    public function getEntity(string $entityType, ?string $id = null): ?OrmEntity {
        if ($id === null) {
            return null;
        }
        return $this->entities["{$entityType}:{$id}"] ?? null;
    }

    public function saveEntity(OrmEntity $entity, array $options = []): void {
        $this->savedEntities[] = $entity;
    }
}

// ---------------------------------------------------------
// Test 1: Service Initialization & Constants
// ---------------------------------------------------------
echo "Test Suite 1: Service Architecture & Configuration Defaults\n";

$config = new MockConfig([]);
$acl = new MockAcl();
$em = new MockEntityManager();
$service = new ConversationSummarizerService($em, $config, $acl);

assertCondition("Service instance created successfully", $service instanceof ConversationSummarizerService);
assertCondition("Default model is llama-3.3-70b-versatile", ConversationSummarizerService::DEFAULT_MODEL === 'llama-3.3-70b-versatile');
assertCondition("Groq API URL points to chat/completions endpoint", ConversationSummarizerService::GROQ_API_URL === 'https://api.groq.com/openai/v1/chat/completions');
assertCondition("Supported models include llama-3.3-70b-versatile", in_array('llama-3.3-70b-versatile', ConversationSummarizerService::SUPPORTED_MODELS, true));
assertCondition("Supported entities include Meeting and Opportunity", in_array('Meeting', ConversationSummarizerService::SUPPORTED_ENTITIES, true) && in_array('Opportunity', ConversationSummarizerService::SUPPORTED_ENTITIES, true));

// ---------------------------------------------------------
// Test 2: API Key & Model Resolution Hierarchy
// ---------------------------------------------------------
echo "\nTest Suite 2: API Key & Model Resolution\n";

assertCondition("API key is null when not configured", $service->getApiKey() === null);
assertCondition("Override key takes highest precedence", $service->getApiKey('override_key_123') === 'override_key_123');

$configWithKey = new MockConfig(['groqApiKey' => 'config_groq_key_abc']);
$serviceWithConfigKey = new ConversationSummarizerService($em, $configWithKey, $acl);
assertCondition("Config key resolved when present", $serviceWithConfigKey->getApiKey() === 'config_groq_key_abc');
assertCondition("Override still beats config key", $serviceWithConfigKey->getApiKey('override_beats_config') === 'override_beats_config');

assertCondition("Default model used when none configured", $service->getModel() === 'llama-3.3-70b-versatile');
assertCondition("Model override applied when specified", $service->getModel('llama-3.1-8b-instant') === 'llama-3.1-8b-instant');

$configWithModel = new MockConfig(['groqModel' => 'mixtral-8x7b-32768']);
$serviceWithConfigModel = new ConversationSummarizerService($em, $configWithModel, $acl);
assertCondition("Config model resolved properly", $serviceWithConfigModel->getModel() === 'mixtral-8x7b-32768');

// ---------------------------------------------------------
// Test 3: Status Endpoint Output
// ---------------------------------------------------------
echo "\nTest Suite 3: Integration Status & Readiness Telemetry\n";

$statusUnconfigured = $service->getStatus();
assertCondition("Status reports ready status", $statusUnconfigured['status'] === 'ready');
assertCondition("Status reports Groq provider", $statusUnconfigured['provider'] === 'Groq');
assertCondition("Configured is false when key absent", $statusUnconfigured['configured'] === false);
assertCondition("Status exposes supported entities list", count($statusUnconfigured['supportedEntities']) >= 5);

$statusConfigured = $serviceWithConfigKey->getStatus();
assertCondition("Configured is true when key present", $statusConfigured['configured'] === true);

// ---------------------------------------------------------
// Test 4: Entity Conversation Extraction
// ---------------------------------------------------------
echo "\nTest Suite 4: Entity Conversation Context Extraction\n";

$meetingEntity = new MockEntity('Meeting', [
    'id' => 'meet-001',
    'name' => 'Follow up with Rahul Sharma',
    'status' => 'Planned',
    'dateStart' => '2026-09-29 10:00:00',
    'accountName' => 'Tech Solutions',
    'parentType' => 'Opportunity',
    'parentName' => 'Tech Solutions CRM Project',
    'description' => 'Discussed enterprise CRM deployment, user licensing for 25 seats, and cloud migration timeline.'
]);

$oppEntity = new MockEntity('Opportunity', [
    'id' => 'opp-001',
    'name' => 'Tech Solutions CRM Project',
    'stage' => 'Prospecting',
    'amount' => 50000,
    'amountCurrency' => 'USD',
    'closeDate' => '2026-12-31',
    'accountName' => 'Tech Solutions',
    'originalLeadName' => 'Rahul Sharma',
    'description' => 'High-potential CRM deployment opportunity for Tech Solutions enterprise accounts.'
]);

$emEntities = new MockEntityManager([
    'Meeting:meet-001' => $meetingEntity,
    'Opportunity:opp-001' => $oppEntity,
]);

$serviceEntities = new ConversationSummarizerService($emEntities, $config, $acl);

$extractedMeeting = $serviceEntities->extractConversationFromEntity('Meeting', 'meet-001');
assertCondition("Meeting entity name resolved", $extractedMeeting['entityName'] === 'Follow up with Rahul Sharma');
assertCondition("Meeting conversation includes account name", str_contains($extractedMeeting['fullText'], 'Tech Solutions'));
assertCondition("Meeting conversation includes description", str_contains($extractedMeeting['fullText'], 'enterprise CRM deployment'));
assertCondition("Meeting conversation includes parent opportunity", str_contains($extractedMeeting['fullText'], 'Tech Solutions CRM Project'));

$extractedOpp = $serviceEntities->extractConversationFromEntity('Opportunity', 'opp-001');
assertCondition("Opportunity deal stage included in context", str_contains($extractedOpp['fullText'], 'Prospecting'));
assertCondition("Opportunity amount included in context", str_contains($extractedOpp['fullText'], '50000'));
assertCondition("Originating lead name included in context", str_contains($extractedOpp['fullText'], 'Rahul Sharma'));

// ---------------------------------------------------------
// Test 5: ACL & Error Handling in Extraction
// ---------------------------------------------------------
echo "\nTest Suite 5: ACL Protection & Error Handling\n";

$aclForbidden = new MockAcl(['Meeting' => [Table::ACTION_READ => false]]);
$serviceForbidden = new ConversationSummarizerService($emEntities, $config, $aclForbidden);

try {
    $serviceForbidden->extractConversationFromEntity('Meeting', 'meet-001');
    assertCondition("Forbidden exception thrown on ACL denial", false);
} catch (Forbidden $e) {
    assertCondition("Forbidden exception thrown on ACL denial", true);
}

try {
    $serviceEntities->extractConversationFromEntity('Meeting', 'non-existent-id');
    assertCondition("NotFound exception thrown on missing entity", false);
} catch (NotFound $e) {
    assertCondition("NotFound exception thrown on missing entity", true);
}

// ---------------------------------------------------------
// Test 6: AI Summarization Simulation & Schema Compliance
// ---------------------------------------------------------
echo "\nTest Suite 6: AI Summarization Output Schema\n";

$rawConversation = "Client (Rahul Sharma): We are reviewing CRM vendors for Tech Solutions. We like EspoCRM. Our budget is $50,000.\nRep: Excellent, we can deploy on Docker with Groq AI integration by next week.\nClient: Agreed, let's schedule a formal follow-up to finalize.";

$simSummary = $serviceEntities->simulateSummary($rawConversation, 'Meeting', 'Follow up with Rahul Sharma');
assertCondition("Simulated summary returns non-empty executive summary", !empty($simSummary['summary']));
assertCondition("Key points is an array with at least 2 items", is_array($simSummary['keyPoints']) && count($simSummary['keyPoints']) >= 2);
assertCondition("Action items is an array with at least 2 items", is_array($simSummary['actionItems']) && count($simSummary['actionItems']) >= 2);
assertCondition("Sentiment is classified as Positive", $simSummary['sentiment'] === 'Positive');
assertCondition("Deal temperature is classified as Hot", $simSummary['dealTemperature'] === 'Hot');
assertCondition("Simulated flag is true", $simSummary['simulated'] === true);
assertCondition("Token usage metrics are populated", isset($simSummary['usage']['totalTokens']) && $simSummary['usage']['totalTokens'] > 0);

// Negative conversation test
$negativeText = "Customer called to cancel subscription. Expressed extreme disappointment with recent delays and rejected our price proposal.";
$negSummary = $serviceEntities->simulateSummary($negativeText);
assertCondition("Negative conversation correctly classified as Negative", $negSummary['sentiment'] === 'Negative');
assertCondition("Negative conversation sets Deal Temperature to Cold", $negSummary['dealTemperature'] === 'Cold');

// ---------------------------------------------------------
// Test 7: Full Summarize Workflow (Text & Entity Modes)
// ---------------------------------------------------------
echo "\nTest Suite 7: Full End-to-End Summarize Workflow\n";

// Raw text summarization with simulate flag
$resultRaw = $serviceEntities->summarize([
    'text' => $rawConversation,
    'simulate' => true,
]);

assertCondition("Raw text summarize returns success status", $resultRaw['status'] === 'success');
assertCondition("Source type is raw_text", $resultRaw['data']['source']['type'] === 'raw_text');
assertCondition("Summary data contains keyPoints", count($resultRaw['data']['keyPoints']) > 0);

// Entity-driven summarization with simulate flag
$resultEntity = $serviceEntities->summarize([
    'entityType' => 'Meeting',
    'entityId' => 'meet-001',
    'simulate' => true,
]);

assertCondition("Entity summarize returns success status", $resultEntity['status'] === 'success');
assertCondition("Source type is entity", $resultEntity['data']['source']['type'] === 'entity');
assertCondition("Source entityId matches meet-001", $resultEntity['data']['source']['entityId'] === 'meet-001');

// Validation error when no input given
try {
    $serviceEntities->summarize([]);
    assertCondition("Empty params throws BadRequest", false);
} catch (BadRequest $e) {
    assertCondition("Empty params throws BadRequest", true);
}

// Validation error when key absent and simulate not requested
try {
    $serviceEntities->summarize(['text' => 'Hello']);
    assertCondition("Unconfigured key without simulate throws BadRequest", false);
} catch (BadRequest $e) {
    assertCondition("Unconfigured key without simulate throws BadRequest", true);
}

// ---------------------------------------------------------
// Test 8: Save to Entity Feature
// ---------------------------------------------------------
echo "\nTest Suite 8: Save Summary to Entity Record\n";

$resultSaved = $serviceEntities->summarize([
    'entityType' => 'Meeting',
    'entityId' => 'meet-001',
    'simulate' => true,
    'saveToEntity' => true,
]);

assertCondition("savedToRecord flag is true", $resultSaved['data']['savedToRecord'] === true);
assertCondition("Entity was passed to saveEntity", count($emEntities->savedEntities) > 0);
$updatedMeeting = $emEntities->savedEntities[0];
assertCondition("Meeting description was updated with AI Summary block", str_contains($updatedMeeting->get('description'), '[AI Conversation Summary'));

// ---------------------------------------------------------
// Summary
// ---------------------------------------------------------
echo "\n==================================================\n";
echo "Total Assertions: " . ($passCount + $failCount) . "\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "==================================================\n";

exit($failCount === 0 ? 0 : 1);
