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

namespace Sigwin\Ariadne\Test;

use Sigwin\Ariadne\Model\Collection\NamedResourceCollection;
use Sigwin\Ariadne\Model\Repository;
use Sigwin\Ariadne\Model\RepositoryType;
use Sigwin\Ariadne\Model\RepositoryUser;
use Sigwin\Ariadne\Model\RepositoryVisibility;

trait ModelGeneratorTrait
{
    /**
     * @param list<array{string, string}> $list
     *
     * @return NamedResourceCollection<RepositoryUser>
     */
    private function createUsers(array $list = []): NamedResourceCollection
    {
        return NamedResourceCollection::fromArray(array_map($this->createUser(...), array_column($list, 0), array_column($list, 1)));
    }

    private function createUser(string $name = 'theseus', string $role = 'admin'): RepositoryUser
    {
        return new RepositoryUser($name, $role);
    }

    /**
     * @param list<array{string, string}>|null $users
     */
    private function createRepository(string $path, ?array $users = null): Repository
    {
        return new Repository(
            ['path' => $path],
            RepositoryType::SOURCE,
            RepositoryVisibility::PUBLIC,
            $this->createUsers($users ?? []),
            time(),
            $path,
            [],
            []
        );
    }
}
