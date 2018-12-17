<?php
/**
 *
 */

declare(strict_types=1);

namespace Zend\Expressive\Doctrine\Hydrator;

/**
 * Class ConfigProvider
 *
 * @package Zend\Expressive\Doctrine\Hydrator
 */
class ConfigProvider
{
    /**
     * Return configuration for this component.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return dependency mappings for this component.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
            ],
        ];
    }
}
