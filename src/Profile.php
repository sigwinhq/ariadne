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

namespace Sigwin\Ariadne;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileTemplate;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;

/**
 * @extends \IteratorAggregate<\Sigwin\Ariadne\Model\Repository>
 */
interface Profile extends \IteratorAggregate
{
    public static function getType(): string;

    public static function fromConfig(ProfileConfig $config, ClientInterface $client, ProfileTemplateFactory $templateFactory, CacheItemPoolInterface $cachePool): self;

    public function getApiUser(): ProfileUser;

    public function getApiVersion(): string;

    public function getName(): string;

    public function getSummary(): ProfileSummary;

    /**
     * @return NamedResourceCollection<ProfileTemplate>
     */
    public function getTemplates(): NamedResourceCollection;

    /**
     * @return NamedResourceCollection<ProfileTemplate>
     */
    public function getMatchingTemplates(Repository $repository): NamedResourceCollection;

    /**
     * @return NamedResourceChangeCollection<Repository, NamedResourceChange>
     */
    public function plan(Repository $repository): NamedResourceChangeCollection;

    /**
     * @param NamedResourceChangeCollection<Repository, NamedResourceChange> $plan
     */
    public function apply(NamedResourceChangeCollection $plan): void;
}
