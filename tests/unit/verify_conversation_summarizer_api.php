<?php
/************************************************************************
 * Verification script for ConversationSummarizer Controller & REST API (W5D5)
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

use Espo\Custom\Controllers\ConversationSummarizer as ConversationSummarizerController;
use Espo\Custom\Services\ConversationSummarizerService;
use Espo\Core\Acl;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Entity as OrmEntity;

echo "=== W5D5: AI Conversation Summarizer REST API Verification ===\n\n";

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
// Mock Infrastructure
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
    public function __construct() {}

    public function checkScope(string $scope, ?string $action = null): bool {
        return true;
    }
}

class MockEntityManager extends EntityManager {
    private array $entities = [];
    public array $savedEntities = [];

    public function __construct(array $entities = []) {
        $this->entities = $entities;
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

class MockRequest implements Request {
    public function __construct(
        private array $body = [],
        private string $rawBody = '',
        private array $queryParams = [],
        private string $method = 'POST'
    ) {}

    public function getParsedBody(): \stdClass {
        return (object) $this->body;
    }

    public function getBodyContents(): ?string {
        return $this->rawBody ?: json_encode($this->body);
    }

    public function hasQueryParam(string $name): bool {
        return isset($this->queryParams[$name]);
    }

    public function getQueryParam(string $name): ?string {
        return isset($this->queryParams[$name]) ? (string) $this->queryParams[$name] : null;
    }

    public function getQueryParams(): array {
        return $this->queryParams;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function hasRouteParam(string $name): bool { return false; }
    public function getRouteParam(string $name): ?string { return null; }
    public function getRouteParams(): array { return []; }
    public function getHeader(string $name): ?string { return null; }
    public function hasHeader(string $name): bool { return false; }
    public function getHeaderAsArray(string $name): array { return []; }
    public function getUri(): \Psr\Http\Message\UriInterface {
        throw new \BadMethodCallException();
    }
    public function getResourcePath(): string { return ''; }
    public function getCookieParam(string $name): ?string { return null; }
    public function getServerParam(string $name) { return null; }
    public function getContentType(): ?string { return 'application/json'; }
    public function toPsr7(): \Psr\Http\Message\ServerRequestInterface {
        throw new \BadMethodCallException();
    }
}

// Setup service and controller
$meeting = new MockEntity('Meeting', [
    'id' => 'meet-test-1',
    'name' => 'Tech Solutions Kickoff Meeting',
    'status' => 'Planned',
    'accountName' => 'Tech Solutions',
    'description' => 'Reviewed client proposal. Agreed on $50,000 budget and scheduled deployment.',
]);

$opp = new MockEntity('Opportunity', [
    'id' => 'opp-test-1',
    'name' => 'Tech Solutions CRM Project',
    'stage' => 'Prospecting',
    'amount' => 50000,
    'accountName' => 'Tech Solutions',
    'description' => 'Enterprise deal for CRM rollout across sales team.',
]);

$em = new MockEntityManager([
    'Meeting:meet-test-1' => $meeting,
    'Opportunity:opp-test-1' => $opp,
]);
$config = new MockConfig(['groqApiKey' => 'test_key_sample']);
$acl = new MockAcl();
$service = new ConversationSummarizerService($em, $config, $acl);
$controller = new ConversationSummarizerController($service, $acl);

// ---------------------------------------------------------
// Test 1: Controller Construction & Default Action
// ---------------------------------------------------------
echo "Test Suite 1: Controller Initialization & Defaults\n";

assertCondition("Controller instantiated successfully", $controller instanceof ConversationSummarizerController);
assertCondition("Default action is status", ConversationSummarizerController::$defaultAction === 'status');

// ---------------------------------------------------------
// Test 2: GET /ConversationSummarizer/status
// ---------------------------------------------------------
echo "\nTest Suite 2: Status Endpoint API Contract\n";

$statusReq = new MockRequest([], '', [], 'GET');
$statusResp = $controller->getActionStatus($statusReq);

assertCondition("Status returns array", is_array($statusResp));
assertCondition("Status reports status = ready", ($statusResp['status'] ?? '') === 'ready');
assertCondition("Status reports provider = Groq", ($statusResp['provider'] ?? '') === 'Groq');
assertCondition("Status reports configured = true", ($statusResp['configured'] ?? false) === true);
assertCondition("Status reports active model", !empty($statusResp['model']));
assertCondition("Action alias actionStatus returns identical output", $controller->actionStatus($statusReq) === $statusResp);

// ---------------------------------------------------------
// Test 3: POST /ConversationSummarizer/summarize (Raw Text)
// ---------------------------------------------------------
echo "\nTest Suite 3: POST Raw Text Conversation Summarization\n";

$rawTextReq = new MockRequest([
    'text' => "Customer: Hello, we are finalizing the contract for the CRM project.\nSales: We have the proposal ready for $50k.\nCustomer: Perfect, let's schedule kickoff on Monday.",
    'simulate' => true,
]);

$rawResp = $controller->postActionSummarize($rawTextReq);

assertCondition("POST summarize returns status = success", ($rawResp['status'] ?? '') === 'success');
assertCondition("Data payload contains summary string", !empty($rawResp['data']['summary']));
assertCondition("Data payload contains keyPoints array", is_array($rawResp['data']['keyPoints']) && count($rawResp['data']['keyPoints']) > 0);
assertCondition("Data payload contains actionItems array", is_array($rawResp['data']['actionItems']) && count($rawResp['data']['actionItems']) > 0);
assertCondition("Data payload contains sentiment classification", in_array($rawResp['data']['sentiment'] ?? '', ['Positive', 'Neutral', 'Negative'], true));
assertCondition("Data payload contains dealTemperature classification", in_array($rawResp['data']['dealTemperature'] ?? '', ['Hot', 'Warm', 'Cold'], true));
assertCondition("Source type is raw_text", ($rawResp['data']['source']['type'] ?? '') === 'raw_text');
assertCondition("Action alias actionSummarize produces identical structure", isset($controller->actionSummarize($rawTextReq)['data']['summary']));

// ---------------------------------------------------------
// Test 4: POST /ConversationSummarizer/summarize (Entity Context)
// ---------------------------------------------------------
echo "\nTest Suite 4: POST Entity-Linked Conversation Summarization\n";

$entityReq = new MockRequest([
    'entityType' => 'Meeting',
    'entityId' => 'meet-test-1',
    'simulate' => true,
]);

$entityResp = $controller->postActionSummarize($entityReq);

assertCondition("Entity summarize returns success", ($entityResp['status'] ?? '') === 'success');
assertCondition("Source type is entity", ($entityResp['data']['source']['type'] ?? '') === 'entity');
assertCondition("Source entityType is Meeting", ($entityResp['data']['source']['entityType'] ?? '') === 'Meeting');
assertCondition("Source entityId is meet-test-1", ($entityResp['data']['source']['entityId'] ?? '') === 'meet-test-1');
assertCondition("Source entityName is Tech Solutions Kickoff Meeting", ($entityResp['data']['source']['entityName'] ?? '') === 'Tech Solutions Kickoff Meeting');

// ---------------------------------------------------------
// Test 5: POST with saveToEntity
// ---------------------------------------------------------
echo "\nTest Suite 5: Save Summary to CRM Entity Record via API\n";

$saveReq = new MockRequest([
    'entityType' => 'Opportunity',
    'entityId' => 'opp-test-1',
    'simulate' => true,
    'saveToEntity' => true,
]);

$saveResp = $controller->postActionSummarize($saveReq);

assertCondition("savedToRecord is true in response", ($saveResp['data']['savedToRecord'] ?? false) === true);
assertCondition("Opportunity was saved to repository", count($em->savedEntities) > 0);
$savedOpp = end($em->savedEntities);
assertCondition("Opportunity description updated with AI summary", str_contains((string) $savedOpp->get('description'), '[AI Conversation Summary'));

// ---------------------------------------------------------
// Test 6: Validation Errors Handling
// ---------------------------------------------------------
echo "\nTest Suite 6: API Error Validation & HTTP Exceptions\n";

$emptyReq = new MockRequest([], '{}', [], 'POST');
try {
    $controller->postActionSummarize($emptyReq);
    assertCondition("Empty request throws BadRequest", false);
} catch (BadRequest $e) {
    assertCondition("Empty request throws BadRequest", true);
}

// ---------------------------------------------------------
// Test 7: Verify Route Configuration in routes.json
// ---------------------------------------------------------
echo "\nTest Suite 7: routes.json Registration & Integrity\n";

$routesPath = __DIR__ . '/../../custom/Espo/Custom/Resources/routes.json';
assertCondition("routes.json exists", file_exists($routesPath));

$routesData = json_decode((string) file_get_contents($routesPath), true);
assertCondition("routes.json is valid JSON array", is_array($routesData));

$hasSummarize = false;
$hasStatus = false;
$hasActivitySummary = false;

foreach ($routesData as $r) {
    if (($r['route'] ?? '') === '/ConversationSummarizer/summarize' && ($r['method'] ?? '') === 'post') {
        $hasSummarize = true;
    }
    if (($r['route'] ?? '') === '/ConversationSummarizer/status' && ($r['method'] ?? '') === 'get') {
        $hasStatus = true;
    }
    if (($r['route'] ?? '') === '/ActivitySummary' && ($r['method'] ?? '') === 'get') {
        $hasActivitySummary = true;
    }
}

assertCondition("routes.json registers POST /ConversationSummarizer/summarize", $hasSummarize);
assertCondition("routes.json registers GET /ConversationSummarizer/status", $hasStatus);
assertCondition("routes.json preserves W5D4 GET /ActivitySummary (zero regressions)", $hasActivitySummary);

// ---------------------------------------------------------
// Summary
// ---------------------------------------------------------
echo "\n==================================================\n";
echo "Total Assertions: " . ($passCount + $failCount) . "\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "==================================================\n";

exit($failCount === 0 ? 0 : 1);
