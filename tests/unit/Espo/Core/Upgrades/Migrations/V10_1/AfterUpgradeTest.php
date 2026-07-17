<?php

namespace tests\unit\Espo\Core\Upgrades\Migrations\V10_1;

use Espo\Core\Upgrades\Migrations\V10_1\AfterUpgrade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class AfterUpgradeTest extends TestCase
{
    public function testThemeParamsDefaultToTopNavbarAndRtl(): void
    {
        $this->assertEquals(
            (object) [
                'navbar' => 'top',
                'direction' => 'rtl',
            ],
            $this->prepareThemeParams(null),
        );
    }

    public function testThemeParamsPreserveNavbarAndCustomValues(): void
    {
        $this->assertEquals(
            (object) [
                'navbar' => 'side',
                'custom' => 'value',
                'direction' => 'rtl',
            ],
            $this->prepareThemeParams((object) [
                'navbar' => 'side',
                'custom' => 'value',
                'direction' => 'ltr',
            ]),
        );
    }

    private function prepareThemeParams(mixed $value): object
    {
        $reflection = new ReflectionClass(AfterUpgrade::class);
        $migration = $reflection->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AfterUpgrade::class, 'prepareThemeParams');

        /** @var object */
        return $method->invoke($migration, $value);
    }
}
