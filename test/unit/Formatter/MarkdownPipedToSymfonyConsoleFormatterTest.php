<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(MarkdownPipedToSymfonyConsoleFormatter::class)]
final class MarkdownPipedToSymfonyConsoleFormatterTest extends TestCase
{
    public function testWrite(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $changeToExpect = <<<'EOF'
# Added
 - [BC] Something added
 - Something added

# Changed
 - [BC] Something changed
 - Something changed

# Removed
 - [BC] Something removed
 - Something removed

# Skipped
 - [BC] A failure happened

EOF;

        $output->expects(self::once())
            ->method('writeln')
            ->willReturnCallback(static function (string $output) use ($changeToExpect): void {
                self::assertStringContainsString($changeToExpect, $output);
            });

        (new MarkdownPipedToSymfonyConsoleFormatter($output))->write(Changes::fromList(
            Change::added('Something added', true),
            Change::added('Something added', false),
            Change::changed('Something changed', true),
            Change::changed('Something changed', false),
            Change::removed('Something removed', true),
            Change::removed('Something removed', false),
            Change::skippedDueToFailure(new Exception('A failure happened')),
        ));
    }
}
