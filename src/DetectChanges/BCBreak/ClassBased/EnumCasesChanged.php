<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Regex;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;

use function array_filter;
use function array_map;

final class EnumCasesChanged implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        $fromEnumName = $fromClass->getName();
        $fromKind     = $this->kindOf($fromClass);
        $toKind       = $this->kindOf($toClass);

        if (! $fromClass instanceof ReflectionEnum && ! $toClass instanceof ReflectionEnum) {
            return Changes::empty();
        }

        if (! $fromClass instanceof ReflectionEnum) {
            return Changes::fromList(Change::changed($fromKind . ' ' . $fromEnumName . ' became enum'));
        }

        if (! $toClass instanceof ReflectionEnum) {
            return Changes::fromList(Change::changed('enum ' . $fromEnumName . ' became ' . $toKind));
        }

        $addedCases = array_filter(
            $toClass->getCases(),
            static function (ReflectionEnumCase $case) use ($fromClass): bool {
                if (self::isInternalDocComment($case->getDocComment())) {
                    return false;
                }

                return ! $fromClass->hasCase($case->getName());
            },
        );

        $removedCases = array_filter(
            $fromClass->getCases(),
            static function (ReflectionEnumCase $case) use ($toClass): bool {
                if (self::isInternalDocComment($case->getDocComment())) {
                    return false;
                }

                return ! $toClass->hasCase($case->getName());
            },
        );

        $internalisedCases = array_filter(
            $toClass->getCases(),
            static function (ReflectionEnumCase $case) use ($fromClass) {
                if (! self::isInternalDocComment($case->getDocComment())) {
                    return false;
                }

                $fromClassCase = $fromClass->getCase($case->getName());
                if (! $fromClassCase) {
                    return false;
                }

                return ! self::isInternalDocComment($fromClassCase->getDocComment());
            },
        );

        $nowNotInternalCases = array_filter(
            $toClass->getCases(),
            static function (ReflectionEnumCase $case) use ($fromClass) {
                if (self::isInternalDocComment($case->getDocComment())) {
                    return false;
                }

                $fromClassCase = $fromClass->getCase($case->getName());
                if (! $fromClassCase) {
                    return false;
                }

                return self::isInternalDocComment($fromClassCase->getDocComment());
            },
        );

        $caseRemovedChanges = array_map(
            static function (ReflectionEnumCase $case) use ($fromEnumName): Change {
                $caseName = $case->getName();

                return Change::removed('Case ' . $fromEnumName . '::' . $caseName . ' was removed');
            },
            $removedCases,
        );

        $caseAddedChanges = array_map(
            static function (ReflectionEnumCase $case) use ($fromEnumName): Change {
                $caseName = $case->getName();

                return Change::added('Case ' . $fromEnumName . '::' . $caseName . ' was added');
            },
            $addedCases,
        );

        $caseBecameInternalChanges = array_map(
            static function (ReflectionEnumCase $case) use ($fromEnumName): Change {
                $caseName = $case->getName();

                return Change::changed('Case ' . $fromEnumName . '::' . $caseName . ' was marked "@internal"');
            },
            $internalisedCases,
        );

        $caseBecameNotInternalChanges = array_map(
            static function (ReflectionEnumCase $case) use ($fromEnumName): Change {
                $caseName = $case->getName();

                return Change::changed('Case ' . $fromEnumName . '::' . $caseName . ' had "@internal" removed');
            },
            $nowNotInternalCases,
        );

        return Changes::fromList(
            ...$caseRemovedChanges,
            ...$caseAddedChanges,
            ...$caseBecameInternalChanges,
            ...$caseBecameNotInternalChanges,
        );
    }

    private static function isInternalDocComment(string|null $comment): bool
    {
        return $comment !== null
            && Regex\matches($comment, '/\s+@internal\s+/');
    }

    /** @psalm-return 'enum'|'interface'|'trait'|'class' */
    private function kindOf(ReflectionClass $reflectionClass): string
    {
        if ($reflectionClass->isEnum()) {
            return 'enum';
        }

        if ($reflectionClass->isInterface()) {
            return 'interface';
        }

        if ($reflectionClass->isTrait()) {
            return 'trait';
        }

        return 'class';
    }
}
