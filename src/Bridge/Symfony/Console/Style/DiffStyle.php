<?php

declare(strict_types=1);

/*
 * This file is part of the Sigwin Ariadne project.
 *
 * (c) sigwin.hr
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sigwin\Ariadne\Bridge\Symfony\Console\Style;

use Sigwin\Ariadne\Model\RepositoryChange;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Dumper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\OutputWrapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

final class DiffStyle extends SymfonyStyle
{
    private Dumper $dumper;

    private int $lineLength;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->dumper = new Dumper($output);
        /** @phpstan-ignore-next-line $width */
        $width = (new Terminal())->getWidth() ?: self::MAX_LINE_LENGTH;
        $this->lineLength = min($width - (int) (\DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);

        parent::__construct($input, $output);
    }

    public function diff(RepositoryChange $change): void
    {
        if ($change->isActual()) {
            $this->writeln($this->createBlock([sprintf('%1$s = %2$s', $change->name, Helper::removeDecoration($this->getFormatter(), ($this->dumper)($change->actual)))], null, null, '    '));
        } else {
            $this->writeln($this->createBlock([sprintf('%1$s = %2$s', $change->name, ($this->dumper)($change->actual))], null, 'fg=red', '-   '));
            $this->writeln($this->createBlock([sprintf('%1$s = %2$s', $change->name, ($this->dumper)($change->expected))], null, 'fg=green', '+   '));
        }
    }

    /**
     * @author https://github.com/symfony/symfony
     */
    private function createBlock(iterable $messages, string $type = null, string $style = null, string $prefix = ' ', bool $padding = false, bool $escape = false): array
    {
        $indentLength = 0;
        $prefixLength = Helper::width(Helper::removeDecoration($this->getFormatter(), $prefix));
        $lines = [];

        if ($type !== null) {
            $type = sprintf('[%s] ', $type);
            $indentLength = Helper::width($type);
            $lineIndentation = str_repeat(' ', $indentLength);
        }

        // wrap and add newlines for each element
        $outputWrapper = new OutputWrapper();
        foreach ($messages as $key => $message) {
            if ($escape) {
                $message = OutputFormatter::escape($message);
            }

            $lines = array_merge(
                $lines,
                explode(\PHP_EOL, $outputWrapper->wrap(
                    $message,
                    $this->lineLength - $prefixLength - $indentLength,
                    \PHP_EOL
                ))
            );

            if (\count($messages) > 1 && $key < \count($messages) - 1) {
                $lines[] = '';
            }
        }

        $firstLineIndex = 0;
        if ($padding && $this->isDecorated()) {
            $firstLineIndex = 1;
            array_unshift($lines, '');
            $lines[] = '';
        }

        foreach ($lines as $i => &$line) {
            if ($type !== null) {
                $line = $firstLineIndex === $i ? $type.$line : $lineIndentation.$line;
            }

            $line = $prefix.$line;
            $line .= str_repeat(' ', max($this->lineLength - Helper::width(Helper::removeDecoration($this->getFormatter(), $line)), 0));

            if ($style) {
                $line = sprintf('<%s>%s</>', $style, $line);
            }
        }

        return $lines;
    }
}
