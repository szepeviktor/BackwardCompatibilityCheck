<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ConstantChanged;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\Assertion;

#[CoversClass(ConstantChanged::class)]
final class ConstantChangedTest extends TestCase
{
    public function testWillDetectChangesInConstants(): void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public const a = 'value';
    protected const b = 'value';
    private const c = 'value';
    public const d = 'value';
    public const G = 'value';
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    protected const b = 'value';
    public const d = 'value';
    public const e = 'value';
    public const f = 'value';
    public const g = 'value';
}
PHP
            ,
            $astLocator,
        );

        $comparator = $this->createMock(ClassConstantBased::class);

        $comparator
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(static function (ReflectionClassConstant $from, ReflectionClassConstant $to): Changes {
                $propertyName = $from->getName();

                self::assertSame($propertyName, $to->getName());

                return Changes::fromList(Change::added($propertyName, true));
            });

        Assertion::assertChangesEqual(
            Changes::fromList(
                Change::added('b', true),
                Change::added('d', true),
            ),
            (new ConstantChanged($comparator))(
                (new DefaultReflector($fromLocator))->reflectClass('TheClass'),
                (new DefaultReflector($toLocator))->reflectClass('TheClass'),
            ),
        );
    }
}
