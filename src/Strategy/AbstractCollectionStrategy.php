<?php

namespace Zend\Expressive\Doctrine\Hydrator\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use InvalidArgumentException;
use Zend\Hydrator\Strategy\StrategyInterface;

/**
 * Class AbstractCollectionStrategy
 *
 * @package Zend\Expressive\Doctrine\Hydrator\Strategy
 */
abstract class AbstractCollectionStrategy implements StrategyInterface
{
    /**
     * @var string
     */
    protected $collectionName;

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @var object
     */
    protected $object;

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return AbstractCollectionStrategy
     */
    public function setCollectionName($collectionName)
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param ClassMetadata $metadata
     * @return AbstractCollectionStrategy
     */
    public function setClassMetadata(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return object
     */
    public function getObject(): object
    {
        return $this->object;
    }

    /**
     * @param object $object
     * @return AbstractCollectionStrategy
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function extract($value, ?object $object = null)
    {
        return $value;
    }


    /**
     * Return the collection by value (using the public API)
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    protected function getCollectionFromObjectByValue()
    {
        $object = $this->getObject();
        $getter = 'get' . ucfirst($this->getCollectionName());
        if (! method_exists($object, $getter)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The getter %s to access collection %s in object %s does not exist',
                    $getter,
                    $this->getCollectionName(),
                    get_class($object)
                )
            );
        }
        return $object->$getter();
    }

    /**
     * Return the collection by reference (not using the public API)
     *
     * @return Collection
     */
    protected function getCollectionFromObjectByReference()
    {
        $object       = $this->getObject();
        $refl         = $this->getClassMetadata()->getReflectionClass();
        $reflProperty = $refl->getProperty($this->getCollectionName());

        $reflProperty->setAccessible(true);

        return $reflProperty->getValue($object);
    }

    /**
     * This method is used internally by array_udiff to check if two objects are equal, according to their
     * SPL hash. This is needed because the native array_diff only compare strings
     *
     * @param object $a
     * @param object $b
     * @return int
     */
    protected function compareObjects($a, $b)
    {
        return strcmp(spl_object_hash($a), spl_object_hash($b));
    }
}
