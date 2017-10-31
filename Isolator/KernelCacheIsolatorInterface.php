<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Isolator;

interface KernelCacheIsolatorInterface
{
    const WINDOWS_OS = 'WINDOWS';
    const LINUX_OS = 'LINUX';
    const MAC_OS = 'DARWIN';

    /**
     * @return bool
     */
    public function isApplicable();

    /**
     * @return string
     */
    public function getBackupFolder();

    public function dump();

    public function drop($folder);

    public function verify($folder);

    public function restore();
}
