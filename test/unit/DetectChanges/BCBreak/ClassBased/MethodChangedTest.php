<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\MethodChanged;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\Assertion;

use function strtolower;

#[CoversClass(MethodChanged::class)]
final class MethodChangedTest extends TestCase
{
    public function testWillDetectChangesInMethods(): void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function a() {}
    protected function b() {}
    private function c() {}
    private static function d() {}
    public function G() {}
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    protected function b() {}
    private static function d() {}
    public function e() {}
    public function f() {}
    public function g() {}
}
PHP
            ,
            $astLocator,
        );

        $comparator = $this->createMock(MethodBased::class);

        $comparator
            ->expects(self::exactly(3))
            ->method('__invoke')
            ->willReturnCallback(static function (ReflectionMethod $from, ReflectionMethod $to): Changes {
                $methodName = $from->getName();

                self::assertSame(strtolower($methodName), strtolower($to->getName()));

                return Changes::fromList(Change::added($methodName, true));
            });

        Assertion::assertChangesEqual(
            Changes::fromList(
                Change::added('b', true),
                Change::added('d', true),
                Change::added('G', true),
            ),
            (new MethodChanged($comparator))(
                (new DefaultReflector($fromLocator))->reflectClass('TheClass'),
                (new DefaultReflector($toLocator))->reflectClass('TheClass'),
            ),
        );
    }
}
