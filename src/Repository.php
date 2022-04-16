<?php
declare(strict_types = 1);

namespace Innmind\Doctrine;

use Innmind\Doctrine\Exception\MutationOutsideOfContext;
use Innmind\Specification\Specification;
use Innmind\Immutable\Maybe;
use Doctrine\ORM\{
    EntityManagerInterface,
    EntityRepository,
};

/**
 * @template T of object
 */
final class Repository
{
    private EntityManagerInterface $doctrine;
    /** @var class-string<T> */
    private string $entityClass;
    /** @var \Closure(): bool */
    private \Closure $allowMutation;

    /**
     * @param class-string<T> $entityClass
     * @param \Closure(): bool $allowMutation
     */
    public function __construct(
        EntityManagerInterface $doctrine,
        string $entityClass,
        \Closure $allowMutation = null,
    ) {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
        $this->allowMutation = $allowMutation ?? static fn(): bool => true;
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe
    {
        return Maybe::of($this->doctrine->find($this->entityClass, $id));
    }

    /**
     * @param Id<T> $id
     */
    public function contains(Id $id): bool
    {
        return $this->get($id)->match(
            static fn() => true,
            static fn() => false,
        );
    }

    /**
     * @param T $entity
     *
     * @throws MutationOutsideOfContext
     */
    public function add(object $entity): void
    {
        if (!($this->allowMutation)()) {
            throw new MutationOutsideOfContext;
        }

        $this->doctrine->persist($entity);
    }

    /**
     * @param T $entity
     *
     * @throws MutationOutsideOfContext
     */
    public function remove(object $entity): void
    {
        if (!($this->allowMutation)()) {
            throw new MutationOutsideOfContext;
        }

        $this->doctrine->remove($entity);
    }

    /**
     * @return Matching<T>
     */
    public function matching(Specification $specification): Matching
    {
        $repository = $this->doctrine->getRepository($this->entityClass);

        return Matching::of($this->doctrine, $repository, $specification);
    }

    /**
     * @return Matching<T>
     */
    public function all(): Matching
    {
        $repository = $this->doctrine->getRepository($this->entityClass);

        return Matching::all($this->doctrine, $repository);
    }
}
