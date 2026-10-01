<?php
/************************************************************************
 * Verification script for ActivitySummaryService (W5D4 Step 3)
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

use Espo\Custom\Services\ActivitySummaryService;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Executor\QueryExecutor;

echo "=== W5D4 Step 3: Activity Summary Service Verification ===\n\n";

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

// 1. Verify Class Exists
assertCondition(
    class_exists(ActivitySummaryService::class),
    "ActivitySummaryService class exists and is PSR-4 autoloadable"
);

// 2. Mocking infrastructure for verification
class MockPDOStatement extends \PDOStatement {
    public function __construct(private array $data = []) {}
    public function fetchAll(int $mode = \PDO::FETCH_DEFAULT, mixed ...$args): array {
        return $this->data;
    }
}

class MockQueryExecutor implements QueryExecutor {
    /** @var array<string, array|callable> */
    public array $queryResults = [];
    /** @var Select[] */
    public array $executedQueries = [];

    public function execute(\Espo\ORM\Query\Query $query): \PDOStatement {
        $this->executedQueries[] = $query;
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
    public array $createdBuilders = [];
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
        $this->createdBuilders[$this->currentEntityType][] = $qb;
        return $qb;
    }
}

class MockSelectBuilderFactory extends SelectBuilderFactory {
    public MockSelectBuilder $builder;

    public function __construct() {
        $this->builder = new MockSelectBuilder();
    }

    public function create(): \Espo\Core\Select\SelectBuilder {
        return $this->builder;
    }
}

class MockAcl extends Acl {
    /** @var string[] */
    public array $allowedScopes = ['Meeting', 'Call', 'Task', 'Email', 'Account'];

    public function __construct() {}

    public function checkScope(string $scope, ?string $action = null): bool {
        return in_array($scope, $this->allowedScopes);
    }
}

// Setup test double environment
$mockExecutor = new MockQueryExecutor();
$mockSelectBuilderFactory = new MockSelectBuilderFactory();

// Set simulated DB results
$mockExecutor->queryResults = [
    'Meeting' => [
        ['accountId' => 'acc_001', 'count' => 3],
        ['accountId' => 'acc_002', 'count' => 1],
    ],
    'Call' => function(Select $query) {
        $where = $query->getWhere()?->getRaw() ?? [];
        // Return 5 for parentType = Account on acc_001, and 2 for direct accountId on acc_002
        if (isset($where['parentType']) && $where['parentType'] === 'Account') {
            return [
                ['accountId' => 'acc_001', 'count' => 5],
            ];
        }
        return [
            ['accountId' => 'acc_002', 'count' => 2],
        ];
    },
    'Task' => function(Select $query) {
        $where = $query->getWhere()?->getRaw() ?? [];
        if (isset($where['parentType']) && $where['parentType'] === 'Account') {
            return [
                ['accountId' => 'acc_001', 'count' => 2],
            ];
        }
        return [
            ['accountId' => 'acc_003', 'count' => 4],
        ];
    },
    'Email' => [
        ['accountId' => 'acc_001', 'count' => 7],
        ['accountId' => 'acc_003', 'count' => 1],
    ],
    'Account' => [
        ['id' => 'acc_001', 'name' => 'Acme Corporation'],
        ['id' => 'acc_002', 'name' => 'Beta Global'],
        ['id' => 'acc_003', 'name' => 'Cyberdyne Systems'],
    ],
];

// Reflection setup of EntityManager and Acl mocks
$emReflection = new ReflectionClass(EntityManager::class);
$emMock = $emReflection->newInstanceWithoutConstructor();

// Inject mock queryExecutor into EntityManager
$queryExecutorProp = $emReflection->getProperty('queryExecutor');
$queryExecutorProp->setAccessible(true);
$queryExecutorProp->setValue($emMock, $mockExecutor);

$aclMock = new MockAcl();

// Test with full access granted
$service = new ActivitySummaryService(
    $emMock,
    $mockSelectBuilderFactory,
    $aclMock
);

// 3. Run Query Service
echo "\n--- Running ActivitySummaryService::getActivitySummary(30) ---\n";
$result = $service->getActivitySummary(30);

// 4. Assert Output Structure
assertCondition(isset($result['list']), "Result contains 'list' key");
assertCondition(isset($result['byAccount']), "Result contains 'byAccount' key");
assertCondition(isset($result['total']), "Result contains 'total' key");
assertCondition(isset($result['meta']), "Result contains 'meta' key");

// 5. Assert List Content & Grouping
assertCondition(count($result['list']) > 0, "List contains grouped items");

$firstItem = $result['list'][0];
assertCondition(
    isset($firstItem['accountId']) &&
    isset($firstItem['accountName']) &&
    isset($firstItem['activityType']) &&
    isset($firstItem['count']),
    "Each list item contains accountId, accountName, activityType, and count"
);

// 6. Assert Activity Types Covered
$typesInResult = array_unique(array_column($result['list'], 'activityType'));
sort($typesInResult);
$expectedTypes = ['Call', 'Email', 'Meeting', 'Task'];
sort($expectedTypes);
assertCondition(
    $typesInResult === $expectedTypes,
    "All 4 activity types (Meeting, Call, Task, Email) are represented in the result"
);

// 7. Verify Account Name Resolution & Count Aggregation
$acmeAccount = null;
foreach ($result['byAccount'] as $acc) {
    if ($acc['accountId'] === 'acc_001') {
        $acmeAccount = $acc;
        break;
    }
}

assertCondition($acmeAccount !== null, "Account 'acc_001' found in byAccount");
assertCondition($acmeAccount['accountName'] === 'Acme Corporation', "Account name resolved to 'Acme Corporation'");
assertCondition($acmeAccount['Meeting'] === 3, "acc_001 has 3 Meetings");
assertCondition($acmeAccount['Call'] === 5, "acc_001 has 5 Calls");
assertCondition($acmeAccount['Task'] === 2, "acc_001 has 2 Tasks");
assertCondition($acmeAccount['Email'] === 7, "acc_001 has 7 Emails");
assertCondition($acmeAccount['total'] === 17, "acc_001 has total = 17 activities (3+5+2+7)");

// 8. Verify Call & Task Account Relationship Fallback (parentType vs accountId)
$betaAccount = null;
$cyberdyneAccount = null;
foreach ($result['byAccount'] as $acc) {
    if ($acc['accountId'] === 'acc_002') $betaAccount = $acc;
    if ($acc['accountId'] === 'acc_003') $cyberdyneAccount = $acc;
}

assertCondition($betaAccount['Call'] === 2, "acc_002 correctly resolved 2 Calls via direct accountId fallback");
assertCondition($cyberdyneAccount['Task'] === 4, "acc_003 correctly resolved 4 Tasks via direct accountId fallback");

// 9. Verify Date Threshold in Meta
assertCondition($result['meta']['days'] === 30, "Meta specifies days = 30");
assertCondition(
    preg_match('/^\d{4}-\d{2}-\d{2} 00:00:00$/', $result['meta']['from']) === 1,
    "Meta threshold date formatted as YYYY-MM-DD 00:00:00"
);

// 10. Verify Query Builder Clauses
echo "\n--- Inspecting Generated Query Clauses ---\n";
$meetingQuery = $mockExecutor->executedQueries[0];
$meetingWhere = $meetingQuery->getWhere()?->getRaw() ?? [];

assertCondition(
    $meetingWhere['parentType'] === 'Account' && $meetingWhere['parentId!='] === null,
    "Meeting query correctly uses parentType = 'Account' and parentId != null"
);
assertCondition(
    $meetingWhere['deleted'] === false,
    "Meeting query explicitly enforces deleted = false"
);
assertCondition(
    isset($meetingWhere['dateStart>=']),
    "Meeting query enforces dateStart >= thresholdDate"
);

echo "\n--- Sample Output JSON ---\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "=== Verification Summary ===\n";
echo "Total Passed: $passCount\n";
echo "Total Failed: $failCount\n";

if ($failCount === 0) {
    echo "Result: ALL VERIFICATION CHECKS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "Result: VERIFICATION CHECKS FAILED!\n";
    exit(1);
}
