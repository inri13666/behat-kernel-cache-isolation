<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Legacy;

use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Behat\Testwork\Specification\NoSpecificationsIterator;
use Behat\Testwork\Specification\SpecificationIterator;
use Oro\BehatExtension\KernelCacheBehatExtension\Isolator\KernelCacheIsolatorInterface;
use Oro\BehatExtension\KernelCacheBehatExtension\Service\KernelCacheIsolatorRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OroKernelCacheBehatIsolator implements Isolation\IsolatorInterface, EventSubscriberInterface
{
    use ContainerAwareTrait;

    /** @var KernelCacheIsolatorRegistry */
    protected $engineRegistry;

    /** @var string */
    protected $sid;

    /** @var KernelCacheIsolatorInterface */
    protected $engine;

    /** @var int */
    protected $featuresCount = 0;

    /**
     * @param KernelCacheIsolatorRegistry $engineRegistry
     * @param $installed
     */
    public function __construct(KernelCacheIsolatorRegistry $engineRegistry, $installed)
    {
        $this->engineRegistry = $engineRegistry;
        $this->sid = md5($installed);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeExerciseCompleted::BEFORE => ['countFeatures', 5],
        ];
    }

    /**
     * @param BeforeExerciseCompleted $event
     */
    public function countFeatures(BeforeExerciseCompleted $event)
    {
        $iterator = $event->getSpecificationIterators();
        /** @var SpecificationIterator $exercise */
        foreach ($iterator as $exercise) {
            if (!$exercise instanceof NoSpecificationsIterator) {
                $this->featuresCount += count($exercise->getSuite()->getSetting('paths'));
            }
        }
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
        $event->writeln('<info>[OroKernelCacheBehatIsolator] Dumping current application cache folders</info>');
        $this->findCurrentEngine()->dump();
        $event->writeln('<info>[OroKernelCacheBehatIsolator] Finished</info>');
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
        if (1 < $this->featuresCount) {
            $event->writeln(
                    '<info>[OroKernelCacheBehatIsolator] Restoring kernel cache dump before next feature</info>'
            );
            $this->findCurrentEngine()->restore();
            $event->writeln('<error>[OroKernelCacheBehatIsolator] Dump restored</error>');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Isolation\Event\AfterFinishTestsEvent $event)
    {
        $event->writeln('<info>[OroKernelCacheBehatIsolator] Restoring kernel cache dump</info>');
        $isolator = $this->findCurrentEngine();
        $isolator->restore();
        $isolator->drop($isolator->getBackupFolder());
        $event->writeln('<info>[OroKernelCacheBehatIsolator] finished</info>');
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
        return "OroKernelCacheBehatIsolator";
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'cache';
    }
}
