# Oro Kernel Cache Behat Extension

### Installation

```
    composer require gorgo13/behat-kernel-cache-extension
```

### Behat.yml

```
        Oro\BehatExtension\KernelCacheBehatExtension\OroKernelCacheBehatExtension:
            oro_legacy: ~ # if true then replaces ORO's behat database isolators with this one  
            dump_folder: ~ # default get_sys_temp_dir()
            folders:
                - 'oro'
                - 'doctrine'
```
