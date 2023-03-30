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

namespace Sigwin\Ariadne\Model;

use Sigwin\Ariadne\Model\Change\NamedResourceArrayChangeCollection;
use Sigwin\Ariadne\Model\Change\NamedResourceAttributeUpdate;
use Sigwin\Ariadne\Model\Change\NamedResourceCreate;
use Sigwin\Ariadne\Model\Change\NamedResourceDelete;
use Sigwin\Ariadne\Model\Change\NamedResourceUpdate;
use Sigwin\Ariadne\NamedResource;
use Sigwin\Ariadne\NamedResourceChange;
use Sigwin\Ariadne\NamedResourceChangeCollection;
use Sigwin\Ariadne\NamedResourceCollection;

final class Repository implements NamedResource
{
    /**
     * @param array<string, null|array<string, int|string>|array<string>|bool|int|string> $response
     * @param array<string>                                                               $topics
     * @param array<string>                                                               $languages
     * @param NamedResourceCollection<RepositoryUser>                                     $users
     */
    public function __construct(
        private readonly array $response,
        public readonly RepositoryType $type,
        public readonly RepositoryVisibility $visibility,
        public readonly NamedResourceCollection $users,
        public readonly int $id,
        public readonly string $path,
        public readonly array $topics,
        public readonly array $languages
    ) {
    }

    /**
     * @return NamedResourceChangeCollection<ProfileTemplate, NamedResourceChange>
     */
    public function createChangeForTemplate(ProfileTemplate $template): NamedResourceChangeCollection
    {
        $changes = [];
        foreach ($template->getTargetAttributes($this) as $name => $expected) {
            if (\array_key_exists($name, $this->response) === false) {
                $message = sprintf('Invalid argument "%1$s"', $name);

                $alternatives = [];
                foreach (array_keys($this->response) as $key) {
                    if (levenshtein($name, $key) <= 3) {
                        $alternatives[] = $key;
                    }
                }

                if ($alternatives !== []) {
                    $message .= sprintf(', did you mean %1$s?', implode(', ', $alternatives));
                }

                throw new \InvalidArgumentException($message);
            }
            $actual = $this->response[$name];

            if (\is_array($actual)) {
                throw new \InvalidArgumentException('Unexpected change type found');
            }

            $changes[] = new NamedResourceAttributeUpdate(new Attribute($name), $actual, $expected);
        }

        $this->appendChangesForCollections($changes, $template->getTargetUsers($this), $this->users, static function (?RepositoryUser $expected, ?RepositoryUser $actual): ?array {
            if ($expected === null && $actual === null) {
                throw new \LogicException('Cannot both be null');
            }
            if ($actual === null) {
                return [new NamedResourceAttributeUpdate(new Attribute('role'), null, $expected->role)];
            }
            if ($expected === null) {
                return [new NamedResourceAttributeUpdate(new Attribute('role'), $actual->role, null)];
            }
            if ($actual->role === $expected->role) {
                return null;
            }

            return [new NamedResourceAttributeUpdate(new Attribute('role'), $actual->role, $expected->role)];
        });

        return NamedResourceArrayChangeCollection::fromResource($template, $changes);
    }

    public function getName(): string
    {
        return $this->path;
    }

    /**
     * @template T of NamedResource
     *
     * @param array<\Sigwin\Ariadne\NamedResourceChange> $changes
     * @param NamedResourceCollection<T>                 $expected
     * @param NamedResourceCollection<T>                 $actual
     * @param \Closure(T|null, T|null): (null|array<\Sigwin\Ariadne\NamedResourceChange>) $compare
     */
    private function appendChangesForCollections(array &$changes, NamedResourceCollection $expected, NamedResourceCollection $actual, \Closure $compare): void
    {
        foreach ($expected->diff($actual) as $item) {
            $itemChanges = $compare($item, null);
            if ($itemChanges === null) {
                continue;
            }
            $changes[] = NamedResourceCreate::fromResource($item, $itemChanges);
        }
        foreach ($actual->diff($expected) as $item) {
            $itemChanges = $compare(null, $item);
            if ($itemChanges === null) {
                continue;
            }
            $changes[] = NamedResourceDelete::fromResource($item, $itemChanges);
        }
        foreach ($expected->intersect($actual) as $item) {
            $itemChanges = $compare($item, $actual->get($item->getName()));
            if ($itemChanges === null) {
                continue;
            }
            $changes[] = NamedResourceUpdate::fromResource($item, $itemChanges);
        }
    }
}
