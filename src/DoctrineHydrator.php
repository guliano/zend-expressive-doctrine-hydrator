<?php
/**
 *
 */

declare(strict_types=1);

namespace Zend\Expressive\Doctrine\Hydrator;

use Zend\Hydrator\HydratorInterface;

/**
 * Class DoctrineHydrator
 *
 * @package Zend\Expressive\Doctrine\Hydrator
 */
class DoctrineHydrator implements HydratorInterface
{
    /**
     * @var HydratorInterface
     */
    protected $extractService;

    /**
     * @var HydratorInterface
     */
    protected $hydrateService;

    /**
     * DoctrineHydrator constructor.
     * @param HydratorInterface $extractService
     * @param HydratorInterface $hydrateService
     */
    public function __construct(HydratorInterface $extractService, HydratorInterface $hydrateService)
    {
        $this->extractService = $extractService;
        $this->hydrateService = $hydrateService;
    }

    /**
     * @inheritDoc
     */
    public function extract(object $object): array
    {
        return $this->extractService->extract($object);
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data, object $object)
    {
        return $this->hydrateService->hydrate($data, $object);
    }
}
