<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Isolator;

use Oro\BehatExtension\KernelCacheBehatExtension\Configuration\KernelCacheConfiguration;
use Oro\BehatExtension\KernelCacheBehatExtension\Service\ProcessExecutor;

abstract class AbstractKernelCacheIsolator implements KernelCacheIsolatorInterface
{
    /** @var KernelCacheConfiguration */
    protected $configuration;

    /** @var ProcessExecutor */
    protected $executor;

    /**
     * @return array of applicable OS
     */
    abstract protected function getApplicableOs();

    /**
     * @param ProcessExecutor $executor
     * @param KernelCacheConfiguration $configuration
     */
    public function __construct(ProcessExecutor $executor, KernelCacheConfiguration $configuration = null)
    {
        $this->configuration = $configuration;
        $this->executor = $executor;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable()
    {
        return in_array(explode(' ', strtoupper(php_uname()))[0], $this->getApplicableOs());
    }

    /**
     * @inheritDoc
     */
    public function getBackupFolder()
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->configuration->getDumpFolder(),
            sprintf('behat_cache_isolation_%s', $this->configuration->getSid()),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function dump()
    {
        $this->drop($this->getBackupFolder());
        $this->executor->execute($this->getCreateCommand($this->getBackupFolder()));
        $cacheDir = $this->configuration->getKernelCacheDir();
        foreach ($this->configuration->getFolders() as $folder) {
            $source = implode(DIRECTORY_SEPARATOR, [$cacheDir, $folder]);
            $destination = implode(DIRECTORY_SEPARATOR, [$this->getBackupFolder(), $folder]);
            $this->executor->execute($this->getCopyCommand($source, $destination));
        }
    }

    /**
     * @inheritDoc
     */
    public function drop($folder)
    {
        if ($this->verify($folder)) {
            $this->executor->execute($this->getRemoveCommand($folder));
        }
    }

    /**
     * @inheritDoc
     */
    public function verify($folder)
    {
        return file_exists($folder) && is_dir($folder);
    }

    /**
     * @inheritDoc
     */
    public function restore()
    {
        $cacheDir = $this->configuration->getKernelCacheDir();
        foreach ($this->configuration->getFolders() as $folder) {
            $source = implode(DIRECTORY_SEPARATOR, [$this->getBackupFolder(), $folder]);
            $destination = implode(DIRECTORY_SEPARATOR, [$cacheDir, $folder]);
            if ($this->verify($destination)) {
                $this->drop($destination);
            }
            if ($this->verify($source)) {
                $this->executor->execute($this->getCopyCommand($source, $destination));
            }
        }
    }

    /**
     * @param string $folder
     *
     * @return string
     */
    abstract protected function getRemoveCommand($folder);

    /**
     * @param string $folder
     *
     * @return string
     */
    abstract protected function getCreateCommand($folder);

    /**
     * @param string $source
     * @param string $destination
     *
     * @return string
     */
    abstract protected function getCopyCommand($source, $destination);

    /**
     * @param string $source
     * @param string $destination
     *
     * @return string
     */
    abstract protected function getMoveCommand($source, $destination);

    /**
     * Escapes a string to be used as a shell argument.
     *
     * @param string $argument The argument that will be escaped
     *
     * @return string The escaped argument
     */
    protected function escape($argument)
    {
        //Fix for PHP bug #43784 escapeshellarg removes % from given string
        //Fix for PHP bug #49446 escapeshellarg doesn't work on Windows
        //@see https://bugs.php.net/bug.php?id=43784
        //@see https://bugs.php.net/bug.php?id=49446
        if ('\\' === DIRECTORY_SEPARATOR) {
            if ('' === $argument) {
                return escapeshellarg($argument);
            }

            $escapedArgument = '';
            $quote = false;
            foreach (preg_split('/(")/', $argument, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $part) {
                if ('"' === $part) {
                    $escapedArgument .= '\\"';
                } elseif (self::isSurroundedBy($part, '%')) {
                    // Avoid environment variable expansion
                    $escapedArgument .= '^%"' . substr($part, 1, -1) . '"^%';
                } else {
                    // escape trailing backslash
                    if ('\\' === substr($part, -1)) {
                        $part .= '\\';
                    }
                    $quote = true;
                    $escapedArgument .= $part;
                }
            }
            if ($quote) {
                $escapedArgument = '"' . $escapedArgument . '"';
            }

            return $escapedArgument;
        }

        return escapeshellarg(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $argument));
    }

    private static function isSurroundedBy($arg, $char)
    {
        return 2 < strlen($arg) && $char === $arg[0] && $char === $arg[strlen($arg) - 1];
    }
}
