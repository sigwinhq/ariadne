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

use Sigwin\Ariadne\Bridge\Symfony\Console\Logo;
use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\Model\Change\NamedResourceCreate;
use Sigwin\Ariadne\Model\Change\NamedResourceDelete;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\Profile;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Dumper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\OutputWrapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

final class AriadneStyle extends SymfonyStyle
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

    public function heading(): void
    {
        $this->writeln(sprintf('<info>%1$s</>', Logo::ASCII));
    }

    public function summary(Profile $profile): void
    {
        $this->profile($profile);

        $summary = $profile->getSummary();
        $this->horizontalTable(
            ['API Version', 'API User', 'Repositories', 'Templates'],
            [
                [
                    $profile->getApiVersion(),
                    $profile->getApiUser()->getName(),
                    $this->enumerate($summary->getRepositories()),
                    $this->enumerate($summary->getTemplates()),
                ],
            ]
        );

        if ($this->isVerbose()) {
            $this->section('Templates');

            foreach ($profile->getTemplates() as $template) {
                $this->writeln(sprintf('<info>%1$s</info>', $template->getName()));
                if (\count($template) > 0) {
                    foreach ($template as $repository) {
                        $this->repository($profile, $repository, $template, '    ');
                    }
                } else {
                    $this->error('No matching repositories.');
                }
                $this->newLine();
            }

            if ($this->isVeryVerbose()) {
                $this->section('Repositories');
                foreach ($profile as $repository) {
                    $this->repository($profile, $repository);
                }
            }
        }
    }

    public function profile(Profile $profile): void
    {
        $this->title($profile->getName());
        $summary = $profile->getSummary();
        foreach ($summary->getTemplates() as $template => $count) {
            if ($count === 0) {
                $this->warning(sprintf('Template "%1$s" does not match any repositories.', $template));
            }
        }
    }

    /**
     * @param NamedResourceChangeCollection<\Sigwin\Ariadne\NamedResource, NamedResourceChange> $change
     */
    public function diff(NamedResourceChangeCollection $change): void
    {
        $this->diffCollection($change);
        $this->newLine();
    }

    private function diffCollection(NamedResourceChange $change, int $depth = 0): void
    {
        $resource = $change->getResource();
        $name = match ($resource::class) {
            Repository::class => $resource->getName(),
            RepositoryUser::class => sprintf('user %1$s', $resource->getName()),
            default => null,
        };

        $type = match ($change::class) {
            NamedResourceCreate::class => ' <question>create</question>',
            NamedResourceDelete::class => ' <error>delete</error>',
            default => '',
        };

        if ($name !== null) {
            $this->newLine();
            $this->writeln(sprintf('%1$s<info>%2$s</info>%3$s', str_repeat(' ', $depth * 2), $name, $type));
        }
        if ($change instanceof NamedResourceChangeCollection) {
            foreach ($change as $item) {
                if ($item instanceof NamedResourceChangeCollection) {
                    $this->diffCollection($item, $depth + 1);
                } else {
                    $this->diffChange($item, $depth + 1);
                }
            }
        } else {
            $this->diffChange($change, $depth + 1);
        }
    }

    private function diffChange(NamedResourceChange $change, int $depth): void
    {
        if ($change instanceof NamedResourceAttributeUpdate) {
            $name = $change->getResource()->getName();
            if ($change->isActual()) {
                $this->writeln($this->createBlock([sprintf('%1$s = %2$s', $name, (string) Helper::removeDecoration($this->getFormatter(), ($this->dumper)($change->actual)))], null, null, str_repeat(' ', $depth * 2)));
            } else {
                $this->writeln($this->createBlock([sprintf('%1$s = %2$s', $name, ($this->dumper)($change->actual))], null, 'fg=red', '-'.str_repeat(' ', $depth * 2 - 1)));
                $this->writeln($this->createBlock([sprintf('%1$s = %2$s', $name, ($this->dumper)($change->expected))], null, 'fg=green', '+'.str_repeat(' ', $depth * 2 - 1)));
            }

            return;
        }

        $this->diffCollection($change, $depth + 1);
    }

    private function repository(Profile $profile, Repository $repository, ?ProfileTemplate $template = null, string $prefix = ''): void
    {
        $matching = $profile->getMatchingTemplates($repository);

        $additional = [];
        foreach ($matching as $match) {
            if ($template === null || $match->getName() !== $template->getName()) {
                $additional[] = $match->getName();
            }
        }

        if ($additional !== []) {
            $this->writeln(sprintf('%1$s%2$s <comment>(in <info>%3$s</info>)</comment>', $prefix, $repository->path, implode('</info>, <info>', $additional)));
        } elseif ($template === null) {
            $this->writeln(sprintf('%1$s%2$s <error>(no matching templates)</error>', $prefix, $repository->path));
        } else {
            $this->writeln(sprintf('%1$s%2$s', $prefix, $repository->path));
        }
    }

    /**
     * @param array<string, int> $list
     */
    private function enumerate(array $list): string
    {
        $enumeration = [];
        foreach ($list as $item => $count) {
            if ($count > 0) {
                $enumeration[] = sprintf('%1$s: %2$d', $item, $count);
            } else {
                $enumeration[] = sprintf('<error>%1$s: none</error>', $item);
            }
        }

        return implode(\PHP_EOL, $enumeration);
    }

    /**
     * @param array<string> $messages
     *
     * @return array<string>
     *
     * @author https://github.com/symfony/symfony
     */
    private function createBlock(array $messages, string $type = null, string $style = null, string $prefix = ' ', bool $padding = false, bool $escape = false): array
    {
        $indentLength = 0;
        $prefixLength = Helper::width((string) Helper::removeDecoration($this->getFormatter(), $prefix));
        $lines = [];

        $lineIndentation = '';
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
            $line .= str_repeat(' ', max($this->lineLength - Helper::width((string) Helper::removeDecoration($this->getFormatter(), $line)), 0));

            if ($style !== null) {
                $line = sprintf('<%s>%s</>', $style, $line);
            }
        }

        return $lines;
    }
}
