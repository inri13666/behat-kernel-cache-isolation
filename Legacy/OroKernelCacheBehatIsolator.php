<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Legacy;

use Oro\BehatExtension\KernelCacheBehatExtension\Isolator\KernelCacheIsolatorInterface;
use Oro\BehatExtension\KernelCacheBehatExtension\Service\KernelCacheIsolatorRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroKernelCacheBehatIsolator implements Isolation\IsolatorInterface
{
    use ContainerAwareTrait;

    /** @var KernelCacheIsolatorRegistry */
    protected $engineRegistry;

    /** @var string */
    protected $sid;

    /** @var KernelCacheIsolatorInterface */
    protected $engine;

    public function __construct(KernelCacheIsolatorRegistry $engineRegistry, $installed)
    {
        $this->engineRegistry = $engineRegistry;
        $this->sid = md5($installed);
    }

    /**
     * @return KernelCacheIsolatorInterface
     */
    protected function findCurrentEngine()
    {
        if (!$this->engine) {
            $this->engine = $this->engineRegistry->findEngine();
        }

        return $this->engine;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Isolation\Event\BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Dumping current application cache folders</info>');
        $this->findCurrentEngine()->dump();
        $event->writeln('<info>Dump created</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function beforeTest(Isolation\Event\BeforeIsolatedTestEvent $event)
    {
        // Do Nothing
    }

    /**
     * {@inheritdoc}
     */
    public function afterTest(Isolation\Event\AfterIsolatedTestEvent $event)
    {
        $this->findCurrentEngine()->restore();
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Isolation\Event\AfterFinishTestsEvent $event)
    {
        $isolator = $this->findCurrentEngine();
        $isolator->restore();
        $isolator->drop($isolator->getBackupFolder());
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContainerInterface $container)
    {
        $this->setContainer($container);
        try {
            $this->findCurrentEngine();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(Isolation\Event\RestoreStateEvent $event)
    {
        $isolator = $this->findCurrentEngine();
        $event->writeln('<info>Begin to restore the state of kernel cache...</info>');
        if ($isolator->verify($isolator->getBackupFolder())) {
            $event->writeln('<info>Drop/Create Kernel caches</info>');
            $isolator->restore();
            $isolator->drop($isolator->getBackupFolder());
            $event->writeln('<info>Kernel cache was restored from dump</info>');
        } else {
            $event->writeln('<info>Kernel cache was not restored from dump</info>');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        $isolator = $this->findCurrentEngine();

        return $isolator->verify($isolator->getBackupFolder());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "Oro Legacy Kernel Cache Isolator";
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'cache';
    }
}
