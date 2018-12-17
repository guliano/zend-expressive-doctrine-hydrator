<?php
/**
 *
 */

declare(strict_types=1);

namespace Zend\Expressive\Doctrine\Hydrator\Container;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Doctrine\Exception\ConfigurationException;
use Zend\Expressive\Doctrine\Hydrator\DoctrineHydrator;
use Zend\Expressive\Doctrine\Hydrator\DoctrineObject;
use Zend\Expressive\Doctrine\ObjectManagerAwareInterface;
use Zend\Hydrator\AbstractHydrator;
use Zend\Hydrator\Filter\FilterComposite;
use Zend\Hydrator\Filter\FilterInterface;
use Zend\Hydrator\Filter\FilterEnabledInterface;
use Zend\Hydrator\NamingStrategy\NamingStrategyInterface;
use Zend\Hydrator\NamingStrategy\NamingStrategyEnabledInterface;
use Zend\Hydrator\Strategy\StrategyInterface;
use Zend\Hydrator\Strategy\StrategyEnabledInterface;

/**
 * Class DoctrineHydratorFactory
 *
 * @package Zend\Expressive\Doctrine\Hydrator\Container
 */
class DoctrineHydratorFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return DoctrineHydrator
     * @throws ConfigurationException
     */
    public function __invoke(ContainerInterface $container, string $requestedName): DoctrineHydrator
    {
        $config = $container->get('config');
        $hydratorsConfig = $config['doctrine']['hydrators'];

        if (! isset($hydratorsConfig[$requestedName])) {
            throw new ConfigurationException(
                sprintf('Could not retrieve config for %s hydrator service', $requestedName)
            );
        }

        $hydratorConfig = $hydratorsConfig[$requestedName];

        $objectManager = $this->loadObjectManager($container);

        $extractService = null;
        $hydrateService = null;

        $useCustomHydrator = (array_key_exists('hydrator', $hydratorConfig));

        if ($useCustomHydrator) {
            $extractService = $container->get($hydratorConfig['hydrator']);
            $hydrateService = $extractService;
        }

        if (! isset($extractService, $hydrateService)) {
            $doctrineModuleHydrator = new DoctrineObject($objectManager, $hydratorConfig['by_value']);
            $extractService = ($extractService ?: $doctrineModuleHydrator);
            $hydrateService = ($hydrateService ?: $doctrineModuleHydrator);
        }


        $this->configureHydrator($extractService, $container, $hydratorConfig, $objectManager);
        $this->configureHydrator($hydrateService, $container, $hydratorConfig, $objectManager);

        return new DoctrineHydrator($extractService, $hydrateService);
    }

    /**
     * @param ContainerInterface $container
     * @return ObjectManager
     */
    private function loadObjectManager(ContainerInterface $container): ObjectManager
    {
        return $container->get(EntityManagerInterface::class);
    }

    /**
     * @param AbstractHydrator $hydrator
     * @param ContainerInterface $container
     * @param array $config
     * @param ObjectManager $objectManager
     * @throws ConfigurationException
     */
    public function configureHydrator(
        AbstractHydrator $hydrator,
        ContainerInterface $container,
        array $config,
        ObjectManager $objectManager
    ) {
        $this->configureHydratorFilters($hydrator, $container, $config, $objectManager);
        $this->configureHydratorStrategies($hydrator, $container, $config, $objectManager);
        $this->configureHydratorNamingStrategy($hydrator, $container, $config, $objectManager);
    }

    /**
     * @param AbstractHydrator $hydrator
     * @param ContainerInterface $container
     * @param array $config
     * @param ObjectManager $objectManager
     * @throws ConfigurationException
     */
    public function configureHydratorFilters(
        AbstractHydrator $hydrator,
        ContainerInterface $container,
        array $config,
        ObjectManager $objectManager
    ) {
        if (! $hydrator instanceof FilterEnabledInterface
            || ! isset($config['filters'])
            || ! is_array($config['filters'])
        ) {
            return;
        }

        foreach ($config['filters'] as $name => $filterConfig) {
            $conditionMap = [
                'and' => FilterComposite::CONDITION_AND,
                'or' => FilterComposite::CONDITION_OR,
            ];

            $condition = isset($filterConfig['condition']) ?
                $conditionMap[$filterConfig['condition']] :
                FilterComposite::CONDITION_OR;

            $filterService = $filterConfig['filter'];

            if (! $container->has($filterService)) {
                throw new ConfigurationException(
                    sprintf('Invalid filter %s for field %s: service does not exist', $filterService, $name)
                );
            }

            $filterService = $container->get($filterService);

            if (! $filterService instanceof FilterInterface) {
                throw new ConfigurationException(
                    sprintf('Filter service %s must implement FilterInterface', get_class($filterService))
                );
            }

            if ($filterService instanceof ObjectManagerAwareInterface) {
                $filterService->setObjectManager($objectManager);
            }

            $hydrator->addFilter($name, $filterService, $condition);
        }
    }

    /**
     * @param AbstractHydrator $hydrator
     * @param ContainerInterface $container
     * @param array $config
     * @param ObjectManager $objectManager
     * @throws ConfigurationException
     */
    public function configureHydratorStrategies(
        AbstractHydrator $hydrator,
        ContainerInterface $container,
        array $config,
        ObjectManager $objectManager
    ) {
        if (! $hydrator instanceof StrategyEnabledInterface
            || ! isset($config['strategies'])
            || ! is_array($config['strategies'])
        ) {
            return;
        }

        foreach ($config['strategies'] as $field => $strategyKey) {
            if (! $container->has($strategyKey)) {
                throw new ConfigurationException(sprintf('Invalid strategy %s for field %s', $strategyKey, $field));
            }

            $strategy = $container->get($strategyKey);

            if (! $strategy instanceof StrategyInterface) {
                throw new ConfigurationException(
                    sprintf('Invalid strategy class %s for field %s', get_class($strategy), $field)
                );
            }

            // Attach object manager:
            if ($strategy instanceof ObjectManagerAwareInterface) {
                $strategy->setObjectManager($objectManager);
            }

            $hydrator->addStrategy($field, $strategy);
        }
    }

    /**
     * @param AbstractHydrator $hydrator
     * @param ContainerInterface $container
     * @param array $config
     * @param ObjectManager $objectManager
     * @throws ConfigurationException
     */
    public function configureHydratorNamingStrategy(
        AbstractHydrator $hydrator,
        ContainerInterface $container,
        array $config,
        ObjectManager $objectManager
    ) {
        if (! ($hydrator instanceof NamingStrategyEnabledInterface) || ! isset($config['naming_strategy'])) {
            return;
        }

        $namingStrategyKey = $config['naming_strategy'];
        if (! $container->has($namingStrategyKey)) {
            throw new ConfigurationException(sprintf('Invalid naming strategy %s.', $namingStrategyKey));
        }

        $namingStrategy = $container->get($namingStrategyKey);
        if (! $namingStrategy instanceof NamingStrategyInterface) {
            throw new ConfigurationException(
                sprintf('Invalid naming strategy class %s', get_class($namingStrategy))
            );
        }

        // Attach object manager:
        if ($namingStrategy instanceof ObjectManagerAwareInterface) {
            $namingStrategy->setObjectManager($objectManager);
        }

        $hydrator->setNamingStrategy($namingStrategy);
    }
}
