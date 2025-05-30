<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\UseClassBasedChecksOnATrait;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function uniqid;

#[CoversClass(UseClassBasedChecksOnATrait::class)]
final class UseClassBasedChecksOnATraitTest extends TestCase
{
    public function testCompare(): void
    {
        $changes = Changes::fromList(Change::added(uniqid('foo', true), true));

        $classBased = $this->createMock(ClassBased::class);
        $fromTrait  = $this->createMock(ReflectionClass::class);
        $toTrait    = $this->createMock(ReflectionClass::class);

        $classBased
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromTrait, $toTrait)
            ->willReturn($changes);

        self::assertSame($changes, (new UseClassBasedChecksOnATrait($classBased))($fromTrait, $toTrait));
    }
}
