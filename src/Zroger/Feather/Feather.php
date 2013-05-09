<?php

namespace Zroger\Feather;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\ExecutableFinder;

class Feather
{
    /**
     * Path to the directory to be used as the server root.
     * @var string
     */
    protected $serverRoot;

    /**
     * Path to the httpd.conf file.
     * @var string
     */
    protected $configFile;

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
     * File path to the error log.
     * @var string
     */
    protected $errorLog;

    /**
     * File path to the access log.
     * @var string
     */
    protected $accessLog;

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

    public function __construct($serverRoot, $documentRoot)
    {
        $this->serverRoot = $serverRoot;
        $this->documentRoot = $documentRoot;

        // Default values.
        $this->configFile = $serverRoot . '/httpd.conf';
        $this->port = 80;
        $this->logLevel = 'info';
        $this->modules = array();
        $this->errorLog = $serverRoot . '/error_log';
        $this->accessLog = $serverRoot . '/access_log';
        $this->template = 'default.conf';
    }

    public function start()
    {
        $this->renderConfigFile();

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

        return $process;
    }

    public function stop()
    {
        $process = $this->getProcess('stop');
        $process->run();
        return $process->isSuccessful();
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
        $this->serverRoot = $serverRoot;

        return $this;
    }

    /**
     * Gets the Path to the httpd.conf file..
     *
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Sets the Path to the httpd.conf file..
     *
     * @param string $configFile the configFile
     *
     * @return self
     */
    public function setConfigFile($configFile)
    {
        $this->configFile = $configFile;

        return $this;
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
        $this->modules = $modules;

        return $this;
    }

    /**
     * Gets the File path to the error log..
     *
     * @return string
     */
    public function getErrorLog()
    {
        return $this->errorLog;
    }

    /**
     * Sets the File path to the error log..
     *
     * @param string $errorLog the errorLog
     *
     * @return self
     */
    public function setErrorLog($errorLog)
    {
        $this->errorLog = $errorLog;

        return $this;
    }

    /**
     * Gets the File path to the access log..
     *
     * @return string
     */
    public function getAccessLog()
    {
        return $this->accessLog;
    }

    /**
     * Sets the File path to the access log..
     *
     * @param string $accessLog the accessLog
     *
     * @return self
     */
    public function setAccessLog($accessLog)
    {
        $this->accessLog = $accessLog;

        return $this;
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
