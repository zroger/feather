<?php

namespace Zroger\Feather;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Yaml\Yaml;
use Zroger\Feather\Config\AppConfig;
use Zroger\Feather\Config\YamlConfigLoader;

class Feather
{
    /**
     * Path to the directory to be used as the server root.
     * @var string
     */
    protected $serverRoot;

    /**
     * Path to the document root.
     * @var string
     */
    protected $documentRoot;

    /**
     * Port for apache to listen on.
     * @var int
     */
    protected $port;

    /**
     * Apache log level.
     * @var string
     */
    protected $logLevel;

    /**
     * Array of module files, keyed by module name, e.g.  php5_module => libphp5.so.
     * @var array
     */
    protected $modules;

    /**
     * Filename of the template to be used for rendering the httpd.conf.
     * @var string
     */
    protected $template;

    /**
     * Full path to the httpd or apache2 executable.
     * @var string
     */
    protected $executable;

    /**
     * PSR3 Logger.
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The location of the user config file, or false to skip loading.
     * @var string
     */
    protected $userConfigFile;

    /**
     * The location of the local config file, or false to skip loading.
     * @var string
     */
    protected $localConfigFile;

    /**
     * The list of potential module paths, ordered from most preferred to least.
     * @var array
     */
    protected $modulePaths;

    /**
     * A flag that gets set to true after the environment has been prepared.
     * @var boolean
     */
    private $prepared;

    public function __construct()
    {
        // Default values.
        $this->template = 'default.conf';
    }

    public function start()
    {
        $this->prepareEnvironment();

        $process = $this->getProcess('start', array('-e', $this->getLogLevel()));
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

        $this->getLogger()->info(
            'Listening on localhost:%port, CTRL+C to stop.',
            array('%port' => $this->getPort())
        );

        $this->getLogger()->debug(
            'Server Root => %server_root',
            array('%server_root' => $this->getServerRoot())
        );

        return $process;
    }

    public function stop()
    {
        $this->prepareEnvironment();

        $process = $this->getProcess('stop');
        $process->run();

        $this->getLogger()->info('Shutting down...');

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf(
                    "Apache failed to stop using the following command:\n%s\n%s",
                    $process->getCommandLine(),
                    $process->getErrorOutput()
                )
            );
        }

        $this->getLogger()->info('Server successfully stopped.');
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
     * Gets the Path to the directory to be used as the server root..
     *
     * @return string
     */
    public function getServerRoot()
    {
        if (!isset($this->serverRoot)) {
            $this->serverRoot = posix_getcwd() . '/.feather';
        }
        return $this->serverRoot;
    }

    /**
     * Sets the Path to the directory to be used as the server root..
     *
     * @param string $serverRoot the serverRoot
     *
     * @return self
     */
    public function setServerRoot($serverRoot)
    {
        $this->serverRoot = realpath($serverRoot);

        return $this;
    }

    /**
     * Gets the Path to the httpd.conf file..
     *
     * @return string
     */
    public function getConfigFile()
    {
        return $this->getServerRoot() . '/httpd.conf';
    }

    /**
     * Gets the Path to the document root..
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    /**
     * Sets the Path to the document root..
     *
     * @param string $documentRoot the documentRoot
     *
     * @return self
     */
    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;

        return $this;
    }

    /**
     * Gets the Port for apache to listen on..
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the Port for apache to listen on..
     *
     * @param int $port the port
     *
     * @return self
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Gets the Apache log level..
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Sets the Apache log level..
     *
     * @param string $logLevel the logLevel
     *
     * @return self
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * Gets the Array of module files, keyed by module name, e.g.  php5_module => libphp5.so..
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Sets the Array of module files, keyed by module name, e.g.  php5_module => libphp5.so..
     *
     * @param array $modules the modules
     *
     * @return self
     */
    public function setModules(array $modules)
    {
        $this->modules = array();
        foreach ($modules as $module => $filename) {
            $this->addModule($module, $filename);
        }

        return $this;
    }

    /**
     * Add a module to be used in the server configuration.
     *
     * @param string $module The module name, e.g. "php5_module"
     * @param string $filename The filename of the module, e.g. "libphp5.so"
     *
     * @return self
     */
    public function addModule($module, $filename)
    {
        if (!isset($this->modules)) {
            $this->modules = array();
        }

        $locator = new FileLocator($this->getModulePaths());
        $this->modules[$module] = $locator->locate($filename, null, true);

        return $this;
    }

    /**
     * Gets the File path to the error log..
     *
     * @return string
     */
    public function getErrorLog()
    {
        return $this->getServerRoot() . '/error_log';
    }

    /**
     * Gets the File path to the access log..
     *
     * @return string
     */
    public function getAccessLog()
    {
        return $this->getServerRoot() . '/access_log';
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
     * Gets the filename of the template to be used for rendering the httpd.conf.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Sets the filename of the template to be used for rendering the httpd.conf.
     *
     * @param string $template the template
     *
     * @return self
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Gets the full path to the httpd or apache2 executable.
     *
     * @return string
     */
    public function getExecutable()
    {
        if (!isset($this->executable)) {
            $executable = false;

            $finder = new ExecutableFinder();
            $names = array('httpd', 'apache2');

            foreach ($names as $name) {
                if ($executable = $finder->find($name)) {
                    break;
                }
            }

            if (!$executable) {
                throw new \RuntimeException('Unable to locate an apache executable, either httpd or apache2.');
            }

            $this->executable = $executable;
        }
        return $this->executable;
    }

    /**
     * Sets the full path to the httpd or apache2 executable.
     *
     * @param string $executable the executable
     *
     * @return self
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;

        return $this;
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
        $builder = ProcessBuilder::create(array($this->getExecutable()))
            ->add('-f')->add($this->getConfigFile())
            ->add('-k')->add($action);

        foreach ($extras as $extra) {
            $builder->add($extra);
        }

        return $builder->getProcess();
    }

    /**
     * Gets the PSR3 Logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->setLogger(new NullLogger());
        }
        return $this->logger;
    }


    /**
     * Sets the PSR3 Logger.
     *
     * @param LoggerInterface $logger the logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the The location of the user config file, or false to skip loading.
     *
     * @return string
     */
    public function getUserConfigFile()
    {
        if (!isset($this->userConfigFile)) {
            $file = getenv('HOME') . '/.feather.yml';
            $this->userConfigFile = file_exists($file) ? $file : false;
        }

        return $this->userConfigFile;
    }

    /**
     * Sets the The location of the user config file, or false to skip loading.
     *
     * @param string $userConfigFile the userConfigFile
     *
     * @return self
     */
    public function setUserConfigFile($userConfigFile)
    {
        $this->userConfigFile = $userConfigFile;

        return $this;
    }

    /**
     * Gets the The location of the local config file, or false to skip loading.
     *
     * @return string
     */
    public function getLocalConfigFile()
    {
        if (!isset($this->localConfigFile)) {
            $file = posix_getcwd() . '/feather.yml';
            $this->localConfigFile = file_exists($file) ? $file : false;
        }

        return $this->localConfigFile;
    }

    /**
     * Sets the The location of the local config file, or false to skip loading.
     *
     * @param string $localConfigFile the localConfigFile
     *
     * @return self
     */
    public function setLocalConfigFile($localConfigFile)
    {
        $this->localConfigFile = $localConfigFile;

        return $this;
    }

    /**
     * Get the list of potential module paths, ordered from most preferred
     * to least.
     *
     * @return array Sorted array of possible module paths.
     */
    public function getModulePaths()
    {
        if (!isset($this->modulePaths)) {
            $dirs = array();

            // PHP 5.3 from josegonzalez/php/php53 homebrew tap.
            if (exec('which brew')) {
                if ($php_dir = exec('brew --prefix php53')) {
                    $dirs[] = $php_dir . '/libexec/apache2';
                }
            }

            // CentOS
            $dirs[] = '/usr/lib64/httpd/modules/';

            // Ubuntu
            $dirs[] = '/usr/lib/apache2/modules';

            // osx default apache.
            $dirs[] = '/usr/libexec/apache2';

            $this->modulePaths = $dirs;
        }
        return $this->modulePaths;
    }

    protected function asTemplateVars()
    {
        return array(
            'server_root' => $this->getServerRoot(),
            'config_file' => $this->getConfigFile(),
            'document_root' => $this->getDocumentRoot(),
            'port' => $this->getPort(),
            'log_level' => $this->getLogLevel(),
            'modules' => $this->getModules(),
            'error_log' => $this->getErrorLog(),
            'access_log' => $this->getAccessLog(),
        );
    }

    protected function prepareEnvironment()
    {
        if (!$this->prepared) {
            $this->loadConfigFiles();
            $this->renderConfigFile();
            $this->prepared = true;
        }
    }

    /**
     * Load user config and project config files.  Values from these files are
     * used to set properties on this object if they are not set.
     */
    protected function loadConfigFiles()
    {
        $configs = array();
        $loader = new YamlConfigLoader(new FileLocator());

        if ($file = $this->getUserConfigFile()) {
            $userConfig = $loader->load($file);
            $configs[] = $userConfig;
            $this->getLogger()->info("Loading user config from $file.");
            $this->getLogger()->debug('user config => ' . print_r($userConfig, true));
        }

        if ($file = $this->getLocalConfigFile()) {
            $localConfig = $loader->load($file);
            $configs[] = $localConfig;
            $this->getLogger()->info("Loading local config from $file.");
            $this->getLogger()->debug('local config => ' . print_r($localConfig, true));
        }

        $processor = new Processor();
        $configuration = new AppConfig();
        $config = $processor->processConfiguration($configuration, $configs);

        $this->getLogger()->debug('processed config => ' . print_r($config, true));

        foreach ($config as $key => $value) {
            $words = explode('_', $key);
            $words = array_map('ucfirst', $words);
            $property = lcfirst(implode('', $words));
            $setter = 'set' . implode('', $words);

            if (!isset($this->$property)) {
                $this->$setter($value);
            }
        }

        $this->setModules($config['modules']);
    }

    protected function renderConfigFile()
    {
        $this->verifyServerRoot();

        $loader = new \Twig_Loader_Filesystem(__DIR__ . "/templates");
        $twig = new \Twig_Environment($loader, array('autoescape' => false));

        $rendered = $twig->render($this->getTemplate(), $this->asTemplateVars());
        if (file_put_contents($this->getConfigFile(), $rendered) === false) {
            throw new \RuntimeException(sprintf('Error rendering config file to %s.', $this->getConfigFile()));
        }
        return $this;
    }

    protected function verifyServerRoot()
    {
        $dir = $this->getServerRoot();
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
