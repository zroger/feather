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

## Usage

```
feather run [--port port] [docroot]
```

Start up a web server using the specified document root and port number.  Port
defaults to 8080, and docroot defaults to the current directory.

## Configuration

Feather will look for a file named `feather.yml` with pre-configured values for
starting the server.  Currently supported values are `root` and `port`.  The
`root` property is relative to the directory containing `feather.yml`.  This
can be very handy to put at the root of your project repo, especially when your
document root is not the same as your project root.

```
root: build/html
port: 9999
```

When searching for the `feather.yml` file, Feather will look first in the
current directory and then traverse up the directory tree either until it finds
an appropriate file, or it reaches the filesystem root.  If you put a config
file at the root of your project, you can run feather from any subdirectory of
your project.
