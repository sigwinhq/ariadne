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
use Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Profile;
use Sigwin\Ariadne\Test\ModelGeneratorTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \Sigwin\Ariadne\Bridge\Symfony\Console\Style\AriadneStyle
 *
 * @uses \Sigwin\Ariadne\Model\Collection\SortedNamedResourceCollection
 * @uses \Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig
 * @uses \Sigwin\Ariadne\Model\ProfileSummary
 * @uses \Sigwin\Ariadne\Model\ProfileTemplate
 * @uses \Sigwin\Ariadne\Model\ProfileTemplateTarget
 * @uses \Sigwin\Ariadne\Model\ProfileUser
 * @uses \Sigwin\Ariadne\Model\Repository
 * @uses \Sigwin\Ariadne\Model\RepositoryUser
 *
 * @small
 */
#[\PHPUnit\Framework\Attributes\Small]
#[\PHPUnit\Framework\Attributes\CoversClass(AriadneStyle::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SortedNamedResourceCollection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Config\ProfileTemplateTargetConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProfileSummary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileTemplate::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\ProfileTemplateTarget::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProfileUser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\Repository::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Sigwin\Ariadne\Model\RepositoryUser::class)]
final class AriadneStyleTest extends TestCase
{
    use ModelGeneratorTrait;

    /**
     * @return array<string, array{0: \Closure(AriadneStyle): void, 1: string}>
     */
    public function provideOutputsCases(): iterable
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
                     [WARNING] Template "tpl3" does not match any repositories.
                    EOT
            ],
            'summary' => [
                function (AriadneStyle $style): void {
                    $style->summary($this->mockProfile('myth2'));
                },
                <<<'EOT'

                    myth2
                    =====
                     [WARNING] Template "tpl3" does not match any repositories.
                     -------------- ---------------
                      API Version    1.2.3
                      API User       theseus
                      Repositories   namespace1: 2
                                     namespace2: 1
                      Templates      tpl1: 1
                                     tpl2: 2
                                     tpl3: none
                     -------------- ---------------
                    EOT
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOutputsCases')]
    public function testOutputs(\Closure $closure, string $output): void
    {
        $command = new Command('ariadne:style');
        $command->setCode(static fn () => 0);
        $tester = new CommandTester($command);
        $tester->execute([], ['interactive' => true, 'decorated' => false]);
        $style = new AriadneStyle(new ArgvInput(), $tester->getOutput());

        $closure($style);

        self::assertSame($output, preg_replace('/\s+$/m', '', $tester->getDisplay(true)));
    }

    private function mockProfile(string $name): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile
            ->expects(self::once())
            ->method('getName')
            ->willReturn($name)
        ;

        $repo1NS1 = self::createRepository('namespace1/repo1');
        $repo2NS1 = self::createRepository('namespace1/repo2');
        $repo1NS2 = self::createRepository('namespace2/repo1');

        $summary = new ProfileSummary(
            SortedNamedResourceCollection::fromArray([
                $repo1NS1,
                $repo1NS2,
                $repo2NS1,
            ]),
            SortedNamedResourceCollection::fromArray([
                $this->createTemplate('tpl1', repositories: [$repo1NS2]),
                $this->createTemplate('tpl2', repositories: [$repo1NS1, $repo2NS1]),
                $this->createTemplate('tpl3'),
            ]),
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
