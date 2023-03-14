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

namespace Sigwin\Ariadne\Test\Bridge\Symfony\Console\Style;

use PHPUnit\Framework\TestCase;
use Sigwin\Ariadne\Bridge\Symfony\Console\Logo;
use Sigwin\Ariadne\Bridge\Symfony\Console\Style\AriadneStyle;
use Sigwin\Ariadne\Model\Collection\NamedResourceCollection;
use Sigwin\Ariadne\Model\Collection\ProfileTemplateCollection;
use Sigwin\Ariadne\Model\Collection\RepositoryCollection;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryVisibility;
use Sigwin\Ariadne\Profile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Bridge\Symfony\Console\Style\AriadneStyle
 *
 * @uses \Sigwin\Ariadne\Model\Collection\ProfileTemplateCollection
 * @uses \Sigwin\Ariadne\Model\Collection\RepositoryCollection
 * @uses \Sigwin\Ariadne\Model\ProfileSummary
 * @uses \Sigwin\Ariadne\Model\Collection\NamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 * @uses \Sigwin\Ariadne\Model\Repository
 *
 * @small
 */
final class AriadneStyleTest extends TestCase
{
    /**
     * @return array<string, array{0: \Closure(AriadneStyle): void, 1: string}>
     */
    protected function provideStyleAndTester(): array
    {
        return [
            'heading' => [
                static function (AriadneStyle $style): void {
                    $style->heading();
                },
                Logo::ASCII,
            ],
            'profile' => [
                function (AriadneStyle $style): void {
                    $style->profile($this->mockProfile('myth1'));
                },
                <<<'EOT'

                    myth1
                    =====
                    EOT
            ],
            'summary' => [
                function (AriadneStyle $style): void {
                    $style->summary($this->mockProfile('myth2'));
                },
                <<<'EOT'

                    myth2
                    =====
                     -------------- ---------------
                      API Version    1.2.3
                      API User       theseus
                      Repositories   namespace1: 2
                                     namespace2: 1
                      Templates
                     -------------- ---------------
                    EOT
            ],
        ];
    }

    /**
     * @dataProvider provideStyleAndTester
     */
    public function testOutputs(\Closure $closure, string $output): void
    {
        $command = new Command('ariadne:style');
        $command->setCode(static fn () => 0);
        $tester = new CommandTester($command);
        $tester->execute([], ['interactive' => true, 'decorated' => false]);
        $style = new AriadneStyle(new ArgvInput(), $tester->getOutput());

        $closure($style);

        static::assertSame($output, preg_replace('/\s+$/m', '', $tester->getDisplay(true)));
    }

    private function mockProfile(string $name): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile
            ->expects(static::once())
            ->method('getName')
            ->willReturn($name)
        ;

        $summary = new ProfileSummary(
            RepositoryCollection::fromArray([
                new Repository(
                    ['path' => 'namespace1/repo1'],
                    RepositoryType::SOURCE,
                    RepositoryVisibility::PUBLIC,
                    NamedResourceCollection::fromArray([]),
                    123,
                    'namespace1/repo1',
                    [],
                    []
                ),
                new Repository(
                    ['path' => 'namespace2/repo1'],
                    RepositoryType::SOURCE,
                    RepositoryVisibility::PUBLIC,
                    NamedResourceCollection::fromArray([]),
                    456,
                    'namespace2/repo1',
                    [],
                    []
                ),
                new Repository(
                    ['path' => 'namespace1/repo2'],
                    RepositoryType::SOURCE,
                    RepositoryVisibility::PUBLIC,
                    NamedResourceCollection::fromArray([]),
                    789,
                    'namespace1/repo2',
                    [],
                    []
                ),
            ]),
            new ProfileTemplateCollection([])
        );
        $profile
            ->method('getSummary')
            ->willReturn($summary)
        ;
        $profile
            ->method('getApiUser')
            ->willReturn(new ProfileUser('theseus'))
        ;
        $profile
            ->method('getApiVersion')
            ->willReturn('1.2.3')
        ;

        return $profile;
    }
}
