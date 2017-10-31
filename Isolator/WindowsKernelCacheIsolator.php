<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Isolator;

class WindowsKernelCacheIsolator extends AbstractKernelCacheIsolator
{
    /**
     * {@inheritDoc}
     */
    protected function getApplicableOs()
    {
        return [static::WINDOWS_OS];
    }

    /**
     * {@inheritDoc}
     */
    protected function getRemoveCommand($folder)
    {
        return sprintf('rd /Q /S %s', $this->escape($folder));
    }

    /**
     * {@inheritDoc}
     */
    protected function getCreateCommand($folder)
    {
        return sprintf('md %s', $this->escape($folder));
    }

    /**
     * {@inheritDoc}
     */
    protected function getCopyCommand($source, $destination)
    {
        return sprintf('xcopy %s %s /E /I /Q /Y /G', $this->escape($source), $this->escape($destination));
    }

    /**
     * {@inheritDoc}
     */
    protected function getMoveCommand($source, $destination)
    {
        return sprintf('%s && %s', $this->getCopyCommand($source, $destination), $this->getRemoveCommand($source));
    }
}
