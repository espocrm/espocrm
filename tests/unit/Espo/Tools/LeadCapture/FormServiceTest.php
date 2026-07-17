<?php

namespace tests\unit\Espo\Tools\LeadCapture;

use Espo\Core\Utils\Theme\Direction;
use Espo\Entities\LeadCapture;
use Espo\Tools\LeadCapture\FormService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FormServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{string, Direction}>
     */
    public static function directionProvider(): iterable
    {
        yield 'Arabic' => ['ar_AR', Direction::Rtl];
        yield 'Persian' => ['fa_IR', Direction::Rtl];
        yield 'Hebrew' => ['he_IL', Direction::Rtl];
        yield 'Urdu' => ['ur_IN', Direction::Rtl];
        yield 'English' => ['en_US', Direction::Ltr];
        yield 'Turkish' => ['tr_TR', Direction::Ltr];
    }

    #[DataProvider('directionProvider')]
    public function testDirectionFromFormLanguage(string $language, Direction $expected): void
    {
        $leadCapture = $this->createMock(LeadCapture::class);
        $leadCapture
            ->method('getFormLanguage')
            ->willReturn($language);

        $reflection = new ReflectionClass(FormService::class);
        /** @var FormService $service */
        $service = $reflection->newInstanceWithoutConstructor();

        $this->assertSame($expected, $service->getDirection($leadCapture));
    }
}
