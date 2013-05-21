<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ProcessBuilder;
use Zroger\Feather\Config\ModuleFinder;
use Zroger\Feather\Config\MultiExecutableFinder;
use Zroger\Feather\Config\YamlConfigLoader;

class Feather extends \Pimple
{
    /**
     * A flag that gets set to true after the environment has been prepared.
     * @var boolean
     */
    private $prepared;

    /**
     * Options:
     *   document_root:     Path to the document root.
     *   server_root:       Path to the directory to be used as the server root.
     *   error_log:
     *   access_log:
     *   httpd_conf:        Path to the httpd.conf file that will be generated.
     *   logger:            PSR3 Logger.
     *   executable:        Full path to the httpd or apache2 executable.
     *   port:              Port for apache to listen on.
     *   log_level:         Apache log level.
     *   template:          Filename of the template to be used for rendering the httpd.conf.
     *   modules:           Array of module files, keyed by module name, e.g.  php5_module => libphp5.so.
     *   module_finder:     ModuleFinder service.
     *   module_paths:      Additional paths for use by the module_finder service.
     */
    public function __construct()
    {
        // Default values.
        $this['cwd'] = $this->share(
            function ($container) {
                return posix_getcwd();
            }
        );

        $this['home'] = $this->share(
            function ($container) {
                return getenv('HOME');
            }
        );

        // Parameters
        $this['document_root'] = '%cwd%';
        $this['server_root'] = '%cwd%/.feather';
        $this['error_log'] = '%server_root%/error_log';
        $this['access_log'] = '%server_root%/access_log';
        $this['httpd_conf'] = '%server_root%/httpd.conf';
        $this['port'] = 8080;
        $this['log_level'] = 'info';
        $this['template'] = 'default.conf';
        $this['modules'] = array(
            "authz_host_module" => "mod_authz_host.so",
            "dir_module"        => "mod_dir.so",
            "env_module"        => "mod_env.so",
            "mime_module"       => "mod_mime.so",
            "log_config_module" => "mod_log_config.so",
            "rewrite_module"    => "mod_rewrite.so",
            "php5_module"       => "libphp5.so",
        );
        $this['module_paths'] = array();
        $this['executable'] = $this->share(
            function ($container) {
                $finder = $container['executable_finder'];
                $names = array('httpd', 'apache2');
                if (!($executable = $finder->find($names))) {
                    throw new \RuntimeException(sprintf('Unable to locate an executable named %s', join(', ', $names)));
                }
                return $executable;
            }
        );

        // Services
        $this['logger'] = $this->share(
            function ($container) {
                return new NullLogger();
            }
        );

        $this['executable_finder'] = $this->share(
            function ($container) {
                return new MultiExecutableFinder();
            }
        );

        $this['module_finder'] = $this->share(
            function ($container) {
                $finder = new ModuleFinder();
                $finder->addPaths($container['module_paths']);
                return $finder;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($id)
    {
        $value = parent::offsetGet($id);
        return is_string($value) ? $this->resolveString($value) : $value;
    }

    /**
     * Replace tokens in a string with values from the DI container.  This
     * allows for DI parameter strings to refer to another DI parameter using
     * %token% syntax.
     *
     * @param  string $string The raw string, potentially with embedded tokens.
     *
     * @return string
     */
    protected function resolveString($string)
    {
        $map = array();
        foreach ($this->keys() as $key) {
            $token = '%' . $key .'%';
            if (strpos($string, $token) !== false) {
                $map[$token] = $this[$key];
            }
        }
        return strtr($string, $map);
    }

    public function start()
    {
        $this->prepareEnvironment();

        $process = $this->getProcess('start', array('-e', $this['log_level']));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf(
                    "Apache failed to start using the following command:\n%s\n%s",
                    $process->getCommandLine(),
                    $process->getErrorOutput()
                )
            );
        }

        $this['logger']->info(
            'Listening on localhost:%port, CTRL+C to stop.',
            array('%port' => $this['port'])
        );

        $this['logger']->debug(
            'Server Root => %server_root',
            array('%server_root' => $this['server_root'])
        );

        return $process;
    }

    public function stop()
    {
        $this->prepareEnvironment();

        $process = $this->getProcess('stop');
        $process->run();

        $this['logger']->info('Shutting down...');

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf(
                    "Apache failed to stop using the following command:\n%s\n%s",
                    $process->getCommandLine(),
                    $process->getErrorOutput()
                )
            );
        }

        $this['logger']->info('Server successfully stopped.');
    }

    public function isRunning()
    {
        if ($pid = $this->getPid()) {
            $process = new Process(sprintf('ps -p "%s"', $pid));
            $process->run();
            return $process->isSuccessful();
        }

        return false;
    }

    /**
     * Get the Process ID of the parent httpd process.
     */
    public function getPid()
    {
        $pidfile = $this->configDir . '/httpd.pid';
        if (file_exists($pidfile) && ($pid = file_get_contents($pidfile))) {
            return $pid;
        }
        return false;
    }

    /**
     * Get an apache process ready to run.
     *
     * @param  string $action The action to pass with -k, usually start or stop.
     * @param  array  $extras Array of additional arguments.
     * @return Symfony\Component\Process An instantiated process object.
     */
    protected function getProcess($action, $extras = array())
    {
        $builder = ProcessBuilder::create(array($this['executable']))
            ->add('-f')->add($this['httpd_conf'])
            ->add('-k')->add($action);

        foreach ($extras as $extra) {
            $builder->add($extra);
        }

        return $builder->getProcess();
    }

    /**
     * Convert values to an array for use in templating.
     * @return array An array of all of the container services and parameters.
     */
    protected function toArray()
    {
        $vars = array();
        foreach ($this->keys() as $key) {
            $vars[$key] = $this[$key];
        }

        foreach ($vars['modules'] as $module => $filename) {
            $vars['modules'][$module] = $this['module_finder']->find($filename);
        }

        return $vars;
    }

    protected function prepareEnvironment()
    {
        if (!$this->prepared) {
            $this->renderConfigFile();
            $this->prepared = true;
        }
    }

    public function loadYamlFile($file)
    {
        $this['logger']->info("Loading configuration from $file.");

        $loader = new YamlConfigLoader();
        $config = $loader->load($file);

        foreach ($config as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    protected function renderConfigFile()
    {
        $this->verifyServerRoot();

        $loader = new \Twig_Loader_Filesystem(__DIR__ . "/templates");
        $twig = new \Twig_Environment($loader, array('autoescape' => false));

        $rendered = $twig->render($this['template'], $this->toArray());
        if (file_put_contents($this['httpd_conf'], $rendered) === false) {
            throw new \RuntimeException(sprintf('Error rendering config file to %s.', $this['httpd_conf']));
        }
        return $this;
    }

    protected function verifyServerRoot()
    {
        $dir = $this['server_root'];
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                throw new \RuntimeException("Unable to create cache directory {$dir}");
            }
            // Nothing in this folder should be committed.
            file_put_contents($dir . "/.gitignore", "*\n");
        }
        return $this;
    }
}
