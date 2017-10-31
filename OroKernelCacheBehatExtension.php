<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension;

use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;

use Oro\BehatExtension\KernelCacheBehatExtension\Configuration\KernelCacheConfiguration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelInterface;

class OroKernelCacheBehatExtension implements Extension
{
    const ORO_LEGACY_NODE = 'oro_legacy';
    const CACHE_FOLDERS = 'folders';
    const DUMP_FOLDER = 'dump_folder';

    /** @var array */
    protected $config = [];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter(self::ORO_LEGACY_NODE)) {
            $this->removeOroLegacy($container);
        }


        $this->loadIsolators($container, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'oro_kernel_cache_extension';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->booleanNode(self::ORO_LEGACY_NODE)->defaultFalse()->end()
                ->scalarNode(self::DUMP_FOLDER)
                    ->info('Folder to dump backup')
                    ->defaultValue(sys_get_temp_dir())
                ->end()
                ->arrayNode(self::CACHE_FOLDERS)
                    ->prototype('scalar')->end()
                    ->info('Cache folders to be isolated')
                    ->defaultValue(['doctrine'])
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.yml');

        $this->config = $config;
        $isLegacy = $config[self::ORO_LEGACY_NODE];
        $container->setParameter(self::ORO_LEGACY_NODE, $isLegacy);

        if ($isLegacy) {
            $loader->load('legacy_services.yml');

            if ($container->hasDefinition('oro_kernel_cache_extension.isolation.test_isolation_subscriber')) {
                $container->removeDefinition('oro_kernel_cache_extension.isolation.test_isolation_subscriber');
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    protected function loadIsolators(ContainerBuilder $container, array $config)
    {
        $services = [
            'oro_kernel_cache_extension.engine.windows',
            'oro_kernel_cache_extension.engine.unix',
        ];
        /** @var KernelInterface $appKernel */
        $appKernel = $container->get(Symfony2Extension::KERNEL_ID);
        $appKernel->registerBundles();
        $installed = $appKernel->getContainer()->hasParameter('installed');

        $configuration = new KernelCacheConfiguration();
        $configuration->setDumpFolder($config[self::DUMP_FOLDER])
            ->setSid($installed)
            ->setKernelCacheDir($appKernel->getCacheDir())
            ->setFolders($config[self::CACHE_FOLDERS]);

        foreach ($services as $service) {
            if ($container->hasDefinition($service)) {
                $definition = $container->getDefinition($service);
                $definition->replaceArgument(1, $configuration);
            }
        }
    }

    protected function removeOroLegacy(ContainerBuilder $container)
    {
        $legacyIsolators = [
            'oro_behat_extension.isolation.windows_file_cache_isolator',
            'oro_behat_extension.isolation.unix_file_cache_isolator',
        ];

        foreach ($legacyIsolators as $isolator) {
            if ($container->has($isolator)) {
                $container->removeDefinition($isolator);
            }
        }
    }
}
