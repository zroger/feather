layout: default
---
## Overview

Feather is the simplest way to use the Apache HTTP server in a local development
environment.

## Per-project installation via composer

To use feather for a single project, simply add feather to your `composer.json`,
file, ususally in the `require-dev` section.

```
{
    "require-dev": {
        "zroger/feather": "*"
    },
    "minimum-stability": "dev",
    "config": {
        "bin-dir": "bin/"
    }
}
```

Run `composer update`, then feather will be located at `bin/feather`.


## System-wide installation via phar download

Feather is even more useful when installed system-wide.  These instructions assume
that you have a `/usr/local/bin` directory in your path.

```
cd /usr/local/bin
curl -o feather http://zroger.github.io/feather/feather.phar
chmod +x feather
```

After this you can run feather from any directory on your system.
