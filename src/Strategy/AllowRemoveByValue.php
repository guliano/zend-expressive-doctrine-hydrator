<?php
/**
 *
 */

declare(strict_types=1);

namespace Zend\Expressive\Doctrine\Hydrator\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use LogicException;

/**
 * Class AllowRemoveByValue
 *
 * @package Zend\Expressive\Doctrine\Hydrator\Strategy
 */
class AllowRemoveByValue extends AbstractCollectionStrategy
{
    /**
     * @inheritDoc
     */
    public function hydrate($value, ?array $data)
    {
        // AllowRemove strategy need "adder" and "remover"
        $adder   = 'add' . ucfirst($this->collectionName);
        $remover = 'remove' . ucfirst($this->collectionName);

        if (! method_exists($this->object, $adder) || ! method_exists($this->object, $remover)) {
            throw new LogicException(
                sprintf(
                    'AllowRemove strategy for DoctrineModule hydrator requires both %s and %s to be defined in %s
                     entity domain code, but one or both seem to be missing',
                    $adder,
                    $remover,
                    get_class($this->object)
                )
            );
        }

        $collection = $this->getCollectionFromObjectByValue();

        if ($collection instanceof Collection) {
            $collection = $collection->toArray();
        }

        $toAdd    = new ArrayCollection(array_udiff($value, $collection, [$this, 'compareObjects']));
        $toRemove = new ArrayCollection(array_udiff($collection, $value, [$this, 'compareObjects']));

        $this->object->$adder($toAdd);
        $this->object->$remover($toRemove);

        return $collection;
    }
}
