<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Isolator;

class UnixKernelCacheIsolator extends AbstractKernelCacheIsolator
{
    /**
     * {@inheritDoc}
     */
    protected function getApplicableOs()
    {
        return [static::LINUX_OS, static::MAC_OS];
    }

    /**
     * {@inheritDoc}
     */
    protected function getRemoveCommand($folder)
    {
        return sprintf('rm -rf %s', $folder);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCreateCommand($folder)
    {
        return sprintf('mkdir -p %s', $folder);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCopyCommand($source, $destination)
    {
        return sprintf('cp %s %s', $this->escape($source), $this->escape($destination));
    }

    /**
     * {@inheritDoc}
     */
    protected function getMoveCommand($source, $destination)
    {
        return sprintf('mv %s %s', $this->escape($source), $this->escape($destination));
    }
}
