<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event as BehatEvent;
use Behat\Testwork\EventDispatcher\Event as TestWorkEvent;

use Oro\BehatExtension\KernelCacheBehatExtension\Service\KernelCacheIsolatorRegistry;
use Oro\Component\Database\Model\DatabaseConfigurationModel;
use Oro\Component\Database\Service\DatabaseEngineRegistry;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KernelCacheIsolationSubscriber implements EventSubscriberInterface
{
    const YES_PATTERN = '/^Y/i';

    /** @var KernelCacheIsolatorRegistry */
    protected $engineRegistry;

    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    /**
     * DatabaseIsolationSubscriber constructor.
     *
     * @param KernelCacheIsolatorRegistry $engineRegistry
     */
    public function __construct(KernelCacheIsolatorRegistry $engineRegistry)
    {
        $this->engineRegistry = $engineRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            TestWorkEvent\BeforeExerciseCompleted::BEFORE => ['beforeExercise', 100],
            BehatEvent\AfterFeatureTested::AFTER => ['afterFeature', -100],
            TestWorkEvent\AfterExerciseCompleted::AFTER => ['afterExercise', -100],
        ];
    }

    public function beforeExercise()
    {
        $this->output->writeln('<comment>OroKernelCacheBehatExtension taking place</comment>');

        $engine = $this->engineRegistry->findEngine();
        $backupName = $engine->getBackupFolder();
        if ($engine->verify($backupName)) {
            $helper = new QuestionHelper();
            $question = new ConfirmationQuestion(
                sprintf(
                    '<question>Isolator discover that last time ' .
                    'environment was not restored properly.' . PHP_EOL
                    . 'Do you what to restore the cache state for ?(Y/n)</question>'
                ),
                true,
                self::YES_PATTERN
            );

            if ($helper->ask($this->input, $this->output, $question)) {
                $this->output->writeln(
                    sprintf('Restoring dump for kernel cache'),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $engine->restore();
            }

            $engine->drop($backupName);
        }

        $this->output->writeln(
            sprintf('Taking dump for kernel cache'),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $engine->dump();

        $this->output->writeln(
            sprintf('Dump created with name "%s"', $backupName),
            OutputInterface::VERBOSITY_VERBOSE
        );
    }

    public function afterFeature()
    {
        $engine = $this->engineRegistry->findEngine();

        $this->output->writeln(
            sprintf('Restoring dump for kernel cache'),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $engine->restore();
    }

    public function afterExercise()
    {
        $engine = $this->engineRegistry->findEngine();
        $backupName = $engine->getBackupFolder();
        $engine->drop($backupName);
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }
}
