<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\ExcludeInternalInterface;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

#[CoversClass(ExcludeInternalInterface::class)]
final class ExcludeInternalInterfaceTest extends TestCase
{
    public function testNormalInterfacesAreNotExcluded(): void
    {
        $locator    = (new BetterReflection())->astLocator();
        $reflector  = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface ANormalInterface {}
PHP
            ,
            $locator,
        ));
        $reflection = $reflector->reflectClass('ANormalInterface');

        $check = $this->createMock(InterfaceBased::class);
        $check->expects(self::once())
              ->method('__invoke')
              ->with($reflection, $reflection)
              ->willReturn(Changes::fromList(Change::removed('foo', true)));

        self::assertEquals(
            Changes::fromList(Change::removed('foo', true)),
            (new ExcludeInternalInterface($check))($reflection, $reflection),
        );
    }

    public function testInternalInterfacesAreExcluded(): void
    {
        $locator    = (new BetterReflection())->astLocator();
        $reflector  = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

/** @internal */
interface AnInternalInterface {}
PHP
            ,
            $locator,
        ));
        $reflection = $reflector->reflectClass('AnInternalInterface');

        $check = $this->createMock(InterfaceBased::class);
        $check->expects(self::never())->method('__invoke');

        self::assertEquals(
            Changes::empty(),
            (new ExcludeInternalInterface($check))($reflection, $reflection),
        );
    }
}
