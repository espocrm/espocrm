<?php
/************************************************************************
 * Automated Verification Script for ActivitySummary REST API (W5D4 Step 4)
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

use Espo\Custom\Controllers\ActivitySummary as ActivitySummaryController;
use Espo\Custom\Services\ActivitySummaryService;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Executor\QueryExecutor;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

echo "=== W5D4 Step 4: Activity Summary REST API Verification ===\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition(bool $cond, string $message): void {
    global $passCount, $failCount;
    if ($cond) {
        echo " [PASS] $message\n";
        $passCount++;
    } else {
        echo " [FAIL] $message\n";
        $failCount++;
    }
}

// 1. Verify Controller Class Exists
assertCondition(
    class_exists(ActivitySummaryController::class),
    "ActivitySummary controller exists and is PSR-4 autoloadable"
);

// 2. Verify routes.json
$routesFile = __DIR__ . '/../../custom/Espo/Custom/Resources/routes.json';
assertCondition(file_exists($routesFile), "custom/Espo/Custom/Resources/routes.json file exists");

$routesContent = file_get_contents($routesFile);
$routes = json_decode($routesContent, true);
assertCondition(is_array($routes), "routes.json contains valid JSON array");

$matchingRoute = null;
foreach ($routes as $r) {
    if (
        isset($r['route']) && $r['route'] === '/ActivitySummary' &&
        isset($r['method']) && strtolower($r['method']) === 'get'
    ) {
        $matchingRoute = $r;
        break;
    }
}

assertCondition($matchingRoute !== null, "Found GET /ActivitySummary route in routes.json");
assertCondition(
    isset($matchingRoute['params']['controller']) && $matchingRoute['params']['controller'] === 'ActivitySummary',
    "Route maps to controller 'ActivitySummary'"
);
assertCondition(
    isset($matchingRoute['params']['action']) && $matchingRoute['params']['action'] === 'summary',
    "Route maps to action 'summary'"
);

// 3. Mock Setup for Service & Controller Verification
class MockPDOStatement extends \PDOStatement {
    public function __construct(private array $data = []) {}
    public function fetchAll(int $mode = \PDO::FETCH_DEFAULT, mixed ...$args): array {
        return $this->data;
    }
}

class MockQueryExecutor implements QueryExecutor {
    public array $queryResults = [];
    public function execute(\Espo\ORM\Query\Query $query): \PDOStatement {
        $from = $query->getFrom();
        $key = is_string($from) ? $from : 'default';
        $data = $this->queryResults[$key] ?? [];
        if (is_callable($data)) {
            $data = $data($query);
        }
        return new MockPDOStatement($data);
    }
}

class MockSelectBuilder extends \Espo\Core\Select\SelectBuilder {
    private string $currentEntityType = '';
    public function __construct() {}
    public function from(string $entityType): self {
        $this->currentEntityType = $entityType;
        return $this;
    }
    public function withStrictAccessControl(): self {
        return $this;
    }
    public function buildQueryBuilder(): QueryBuilder {
        $qb = new QueryBuilder();
        $qb->from($this->currentEntityType);
        return $qb;
    }
}

class MockSelectBuilderFactory extends SelectBuilderFactory {
    public function __construct() {}
    public function create(): \Espo\Core\Select\SelectBuilder {
        return new MockSelectBuilder();
    }
}

class MockAcl extends Acl {
    public bool $allowAccount = true;
    public function __construct() {}
    public function checkScope(string $scope, ?string $action = null): bool {
        if ($scope === 'Account') {
            return $this->allowAccount;
        }
        return true;
    }
}

class MockApiRequest implements Request {
    public function __construct(
        private array $queryParams = [],
        private string $method = 'GET'
    ) {}
    public function getMethod(): string { return $this->method; }
    public function hasQueryParam(string $name): bool { return isset($this->queryParams[$name]); }
    public function getQueryParam(string $name): ?string {
        return isset($this->queryParams[$name]) ? (string) $this->queryParams[$name] : null;
    }
    public function getQueryParams(): array { return $this->queryParams; }
    public function hasRouteParam(string $name): bool { return false; }
    public function getRouteParam(string $name): ?string { return null; }
    public function getRouteParams(): array { return []; }
    public function getHeader(string $name): ?string { return null; }
    public function hasHeader(string $name): bool { return false; }
    public function getHeaderAsArray(string $name): array { return []; }
    public function getUri(): \Psr\Http\Message\UriInterface { throw new \RuntimeException(); }
    public function getResourcePath(): string { return '/ActivitySummary'; }
    public function getBodyContents(): ?string { return null; }
    public function getParsedBody(): \stdClass { return new \stdClass(); }
    public function getCookieParam(string $name): ?string { return null; }
    public function getServerParam(string $name) { return null; }
    public function getContentType(): ?string { return 'application/json'; }
    public function toPsr7(): \Psr\Http\Message\ServerRequestInterface { throw new \RuntimeException(); }
}

$mockExecutor = new MockQueryExecutor();
$mockExecutor->queryResults = [
    'Meeting' => [
        ['accountId' => 'acc_100', 'count' => 4],
    ],
    'Call' => function(Select $query) {
        $where = $query->getWhere()?->getRaw() ?? [];
        if (isset($where['parentType']) && $where['parentType'] === 'Account') {
            return [
                ['accountId' => 'acc_100', 'count' => 6],
            ];
        }
        return [];
    },
    'Task' => function(Select $query) {
        $where = $query->getWhere()?->getRaw() ?? [];
        if (isset($where['parentType']) && $where['parentType'] === 'Account') {
            return [
                ['accountId' => 'acc_100', 'count' => 1],
            ];
        }
        return [];
    },
    'Email' => [
        ['accountId' => 'acc_100', 'count' => 9],
    ],
    'Account' => [
        ['id' => 'acc_100', 'name' => 'Acme Global Innovations'],
    ],
];

$emReflection = new ReflectionClass(EntityManager::class);
$emMock = $emReflection->newInstanceWithoutConstructor();
$queryExecutorProp = $emReflection->getProperty('queryExecutor');
$queryExecutorProp->setAccessible(true);
$queryExecutorProp->setValue($emMock, $mockExecutor);

$aclMock = new MockAcl();
$service = new ActivitySummaryService(
    $emMock,
    new MockSelectBuilderFactory(),
    $aclMock
);

$controller = new ActivitySummaryController(
    $service,
    $aclMock
);

// 4. Test Controller getActionSummary with default 30 days
echo "\n--- Testing Controller getActionSummary (default 30 days) ---\n";
$requestDefault = new MockApiRequest([]);
$response = $controller->getActionSummary($requestDefault);

assertCondition(isset($response['list']), "Response contains 'list' key");
assertCondition(isset($response['byAccount']), "Response contains 'byAccount' key");
assertCondition(isset($response['total']), "Response contains 'total' key");
assertCondition(isset($response['meta']), "Response contains 'meta' key");
assertCondition($response['meta']['days'] === 30, "Default meta days is 30");

assertCondition(count($response['list']) === 4, "Response list contains 4 activity types for acc_100");
assertCondition($response['byAccount'][0]['total'] === 20, "Total count for acc_100 is 20 (4+6+1+9)");
assertCondition($response['total']['all'] === 20, "Total all is 20");

// 5. Test Controller getActionSummary with custom days parameter
echo "\n--- Testing Controller getActionSummary (custom days = 60) ---\n";
$requestCustom = new MockApiRequest(['days' => '60']);
$responseCustom = $controller->getActionSummary($requestCustom);
assertCondition($responseCustom['meta']['days'] === 60, "Custom days parameter properly passed to service (days=60)");

// 6. Test Controller Action Alias
echo "\n--- Testing Controller actionSummary alias ---\n";
$responseAlias = $controller->actionSummary($requestDefault);
assertCondition($responseAlias === $response, "actionSummary alias returns identical result to getActionSummary");

// 7. Test Forbidden Exception when Account access is revoked
echo "\n--- Testing ACL Enforcement (Forbidden on restricted Account scope) ---\n";
$aclMock->allowAccount = false;
$forbiddenCaught = false;
try {
    $controller->getActionSummary($requestDefault);
} catch (Forbidden $e) {
    $forbiddenCaught = true;
    echo " [PASS] Forbidden exception caught as expected: " . $e->getMessage() . "\n";
    $passCount++;
}
if (!$forbiddenCaught) {
    echo " [FAIL] Forbidden exception was NOT thrown when Account access was denied\n";
    $failCount++;
}

// 8. Test BadRequest on invalid days parameter
echo "\n--- Testing Input Validation (BadRequest on negative/zero days) ---\n";
$aclMock->allowAccount = true;
$badRequestCaught = false;
try {
    $badRequest = new MockApiRequest(['days' => '-10']);
    $controller->getActionSummary($badRequest);
} catch (BadRequest $e) {
    $badRequestCaught = true;
    echo " [PASS] BadRequest exception caught for negative days: " . $e->getMessage() . "\n";
    $passCount++;
}
if (!$badRequestCaught) {
    echo " [FAIL] BadRequest exception was NOT thrown for invalid days parameter\n";
    $failCount++;
}

echo "\n--- Sample Controller Response JSON ---\n";
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "=== API Verification Summary ===\n";
echo "Total Passed: $passCount\n";
echo "Total Failed: $failCount\n";

if ($failCount === 0) {
    echo "Result: ALL REST API VERIFICATIONS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "Result: REST API VERIFICATION FAILED!\n";
    exit(1);
}
