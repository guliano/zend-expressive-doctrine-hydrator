<?php
/**
 *
 */

declare(strict_types=1);

namespace Zend\Expressive\Doctrine\Hydrator\Strategy;

/**
 * Class AllowRemoveByReference
 *
 * @package Zend\Expressive\Doctrine\Hydrator\Strategy
 */
class AllowRemoveByReference extends AbstractCollectionStrategy
{
    /**
     * @inheritDoc
     */
    public function hydrate($value, ?array $data)
    {
        $collection      = $this->getCollectionFromObjectByReference();
        $collectionArray = $collection->toArray();

        $toAdd    = array_udiff($value, $collectionArray, [$this, 'compareObjects']);
        $toRemove = array_udiff($collectionArray, $value, [$this, 'compareObjects']);

        foreach ($toAdd as $element) {
            $collection->add($element);
        }

        foreach ($toRemove as $element) {
            $collection->removeElement($element);
        }

        return $collection;
    }
}
