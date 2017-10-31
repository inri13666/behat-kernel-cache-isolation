<?php

namespace Oro\BehatExtension\KernelCacheBehatExtension\Configuration;

class KernelCacheConfiguration
{
    /** @var string */
    protected $dumpFolder;

    /** @var array */
    protected $folders;

    /** @var string */
    protected $sid;

    /** @var string */
    protected $kernelCacheDir;

    /**
     * @return string
     */
    public function getDumpFolder()
    {
        return $this->dumpFolder;
    }

    /**
     * @param string $dumpFolder
     *
     * @return $this
     */
    public function setDumpFolder($dumpFolder)
    {
        $this->dumpFolder = $dumpFolder;

        return $this;
    }

    /**
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param array $folders
     *
     * @return $this
     */
    public function setFolders($folders)
    {
        $this->folders = $folders;

        return $this;
    }

    /**
     * @return string
     */
    public function getSid()
    {
        return $this->sid;
    }

    /**
     * @param string $sid
     *
     * @return $this
     */
    public function setSid($sid)
    {
        $this->sid = md5($sid);

        return $this;
    }

    /**
     * @return string
     */
    public function getKernelCacheDir()
    {
        return $this->kernelCacheDir;
    }

    /**
     * @param string $kernelCacheDir
     *
     * @return $this
     */
    public function setKernelCacheDir($kernelCacheDir)
    {
        $this->kernelCacheDir = $kernelCacheDir;

        return $this;
    }
}
