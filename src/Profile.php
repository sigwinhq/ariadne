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
use Sigwin\Ariadne\Model\Collection\TemplateCollection;
use Sigwin\Ariadne\Model\Config\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileSummary;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryPlan;

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

    public function getTemplates(): TemplateCollection;

    public function getMatchingTemplates(Repository $repository): TemplateCollection;

    public function plan(Repository $repository): RepositoryPlan;

    public function apply(RepositoryPlan $plan): void;
}
