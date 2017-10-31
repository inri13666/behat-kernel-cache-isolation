<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Service;

use Oro\BehatExtension\KernelCacheBehatExtension\Exception\KernelCacheIsolatorNotFound;
use Oro\BehatExtension\KernelCacheBehatExtension\Isolator\KernelCacheIsolatorInterface;

class KernelCacheIsolatorRegistry
{
    /** @var array|KernelCacheIsolatorInterface[] */
    protected $cacheIsolators = [];

    public function addEngine(KernelCacheIsolatorInterface $cacheIsolator, $alias)
    {
        $this->cacheIsolators[$alias] = $cacheIsolator;
    }

    /**
     * @return KernelCacheIsolatorInterface
     */
    public function findEngine()
    {
        foreach ($this->cacheIsolators as $cacheIsolator) {
            if ($cacheIsolator->isApplicable()) {
                return $cacheIsolator;
            }
        }

        throw new KernelCacheIsolatorNotFound('Unable to find applicable kernel cache isolator');
    }
}
