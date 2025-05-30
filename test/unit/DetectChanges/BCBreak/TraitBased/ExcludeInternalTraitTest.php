<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\ExcludeInternalTrait;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

#[CoversClass(ExcludeInternalTrait::class)]
final class ExcludeInternalTraitTest extends TestCase
{
    public function testNormalTraitsAreNotExcluded(): void
    {
        $locator    = (new BetterReflection())->astLocator();
        $reflector  = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

trait ANormalTrait {}
PHP
            ,
            $locator,
        ));
        $reflection = $reflector->reflectClass('ANormalTrait');

        $check = $this->createMock(TraitBased::class);
        $check->expects(self::once())
              ->method('__invoke')
              ->with($reflection, $reflection)
              ->willReturn(Changes::fromList(Change::removed('foo', true)));

        self::assertEquals(
            Changes::fromList(Change::removed('foo', true)),
            (new ExcludeInternalTrait($check))($reflection, $reflection),
        );
    }

    public function testInternalTraitsAreExcluded(): void
    {
        $locator    = (new BetterReflection())->astLocator();
        $reflector  = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

/** @internal */
trait AnInternalTrait {}
PHP
            ,
            $locator,
        ));
        $reflection = $reflector->reflectClass('AnInternalTrait');

        $check = $this->createMock(TraitBased::class);
        $check->expects(self::never())->method('__invoke');

        self::assertEquals(
            Changes::empty(),
            (new ExcludeInternalTrait($check))($reflection, $reflection),
        );
    }
}
