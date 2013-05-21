---
layout: default
---
Feather
=======

Feather is the simplest way to use the Apache HTTP server as a local development
server.

[![Build Status](https://travis-ci.org/zroger/feather.png?branch=develop)](https://travis-ci.org/zroger/feather)

* [Installation](#installation)
* [Usage](#usage)
* [Configuration](#configuration)
* [Options](#options)
* [Example Configuration File](#example-configuration-file)

Installation
------------

Feather is most useful when installed system-wide.  These instructions assume
that you have a `/usr/local/bin` directory in your path.

{% highlight bash %}
cd /usr/local/bin
curl -o feather http://zroger.github.io/feather/feather.phar
chmod +x feather
{% endhighlight %}

After this you can run feather from any directory on your system.

### Composer installation

To use feather for a single project, or extend feather with custom functionality,
simply add feather to your `composer.json` file.

{% highlight json %}
{
    "require": {
        "zroger/feather": "*"
    },
    "minimum-stability": "dev",
    "config": {
        "bin-dir": "bin/"
    }
}
{% endhighlight %}

Run `composer update`, then feather will be located at `bin/feather`.

Usage
-----

{% highlight bash %}
feather run [--port port] [docroot]
{% endhighlight %}

Start up a web server using the specified document root and port number.  Port
defaults to 8080, and docroot defaults to the current directory.

Configuration
-------------

Feather is built to require zero configuration to get a simple server running
development, but sometimes a little configuration is required to make things
easier.  Feather has three methods of configuration, listed in the order in
which they are applied.

### User configuration file

The user configuration file is a yaml file that must be named `.feather.yml` in
the current user's home directory.  This file can be useful for overridding
feather's default configuration for your specific preferences, for example if
you want feather to use port 8888 by default rather than port 8080.  The options
specified in the user configuration file are the least significant and will be
overridden by options in the local configuration file and command-line options.

### Local configuration file

The local configuration file is a yaml file named `feather.yml` in the current
directory.  This file is typically used for project-specific options, such as
specifying a specific document root.  The options in this file override the
user configuration, but not the CLI options.

### Command-line options

The options specified on the command-line override any options from either of
the configuration files.  Only a subset of the configuration options are
available from the command-line.

Options
-------

### document_root

Set the path to the document root.  When set from a configuration file, a
relative path will be resolved as relative to the directory of the configuration
file.  When specified as a CLI option, a relative path will be resolved to the
current working directory.  Defaults to the current working directory.

### port

Set the port for apache to listen on.  Defaults to 8080.

### server_root

Set the ServerRoot directive for the Apache configuration.  Feather also uses
this as the directory where the `httpd.conf` file will be written and where log
files as created.  Relative paths are resolved to the directory of the config
file that this option is being set from.  Defaults to `$CWD/.feather`.

### template

Set the Twig template to be used to generate the `httpd.conf`.  Defaults to
`src/Zroger/Feather/templates/default.conf`.

### log_level

Set the log level to be used by Apache.  Must be one of `debug`, `info`,
`notice`, `warn`, `error`, `crit`, `alert` or `emerg`.  Defaults to `info`.

### modules

Set the Apache modules to be loaded.  This is a list of key/value pairs where
the keys are the module name (like `rewrite_module`) and the value is the file
name (like `mod_rewrite.so`).  Feather will attempt to locate the modules in a
number of well-known locations so you do not need to specify absolute paths.
The default modules are a very minimal set of modules that is still capable of
running a PHP web application.  Default:

{% highlight yaml %}
authz_host_module: mod_authz_host.so
dir_module: mod_dir.so
env_module: mod_env.so
mime_module: mod_mime.so
log_config_module: mod_log_config.so
rewrite_module: mod_rewrite.so
php5_module: libphp5.so
{% endhighlight %}

Example Configuration File
--------------------------

{% highlight yaml %}
# feather.yml
document_root: build/html
port: 8080
server_root: .feather
template: default.conf
log_level: debug
modules:
  authz_host_module: mod_authz_host.so
  dir_module: mod_dir.so
  env_module: mod_env.so
  mime_module: mod_mime.so
  log_config_module: mod_log_config.so
  rewrite_module: mod_rewrite.so
  php5_module: libphp5.so
{% endhighlight %}
