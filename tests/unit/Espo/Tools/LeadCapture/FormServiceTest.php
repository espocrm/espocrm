<?php

namespace tests\unit\Espo\Tools\LeadCapture;

use Espo\Entities\LeadCapture;
use Espo\Tools\LeadCapture\FormService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FormServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function directionProvider(): iterable
    {
        yield 'Arabic' => ['ar_AR', 'rtl'];
        yield 'Persian' => ['fa_IR', 'rtl'];
        yield 'Hebrew' => ['he_IL', 'rtl'];
        yield 'Urdu' => ['ur_IN', 'rtl'];
        yield 'English' => ['en_US', 'ltr'];
        yield 'Turkish' => ['tr_TR', 'ltr'];
    }

    #[DataProvider('directionProvider')]
    public function testDirectionFromFormLanguage(string $language, string $expected): void
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
