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

use Psr\Http\Client\ClientInterface;
use Sigwin\Ariadne\Model\ProfileConfig;
use Sigwin\Ariadne\Model\ProfileUser;
use Sigwin\Ariadne\Model\RepositoryCollection;
use Sigwin\Ariadne\Model\TemplateCollection;

/**
 * @extends \IteratorAggregate<\Sigwin\Ariadne\Model\Repository>
 */
interface Profile extends \IteratorAggregate
{
    public static function fromConfig(ClientInterface $client, ProfileTemplateFactory $templateFactory, ProfileConfig $config): self;

    public function getApiUser(): ProfileUser;

    public function getApiVersion(): string;

    public function getName(): string;

    public function getRepositories(): RepositoryCollection;

    public function getTemplates(): TemplateCollection;
}
