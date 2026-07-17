<?php

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Theme\Direction;
use Espo\Core\Utils\Theme\MetadataProvider;
use Espo\Core\Utils\ThemeManager;
use PHPUnit\Framework\TestCase;

class ThemeManagerTest extends TestCase
{
    public function testDirectionFromConfig(): void
    {
        $manager = $this->createManager(
            configValues: [
                'theme' => 'Espo',
                'themeParams.direction' => 'rtl',
            ],
        );

        $this->assertSame(Direction::Rtl, $manager->getDirection());
    }

    public function testDirectionFromThemeDefault(): void
    {
        $manager = $this->createManager(
            configValues: [
                'theme' => 'Custom',
                'themeParams.direction' => 'invalid',
            ],
            metadataValues: [
                'themes.Custom.params.direction.default' => 'rtl',
            ],
        );

        $this->assertSame(Direction::Rtl, $manager->getDirection());
    }

    public function testInvalidDirectionFallsBackToLtr(): void
    {
        $manager = $this->createManager(
            configValues: [
                'theme' => 'Espo',
                'themeParams.direction' => 'invalid',
            ],
            metadataValues: [
                'themes.Espo.params.direction.default' => 'invalid',
            ],
        );

        $this->assertSame(Direction::Ltr, $manager->getDirection());
    }

    /**
     * @param array<string, mixed> $configValues
     * @param array<string, mixed> $metadataValues
     */
    private function createManager(array $configValues, array $metadataValues = []): ThemeManager
    {
        $config = $this->createMock(Config::class);
        $config
            ->method('get')
            ->willReturnCallback(fn (string $key) => $configValues[$key] ?? null);

        $metadata = $this->createMock(Metadata::class);
        $metadata
            ->method('get')
            ->willReturnCallback(function (array|string $path) use ($metadataValues) {
                $key = is_array($path) ? implode('.', $path) : $path;

                return $metadataValues[$key] ?? null;
            });

        return new ThemeManager(
            $config,
            $metadata,
            $this->createMock(MetadataProvider::class),
        );
    }
}
