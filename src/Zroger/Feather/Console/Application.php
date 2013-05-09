<?php

namespace Zroger\Feather\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

use Zroger\Feather\Config\AppConfig;
use Zroger\Feather\DependencyInjection\Compiler\HttpdConfCompilerPass;
use Zroger\Feather\DependencyInjection\FeatherExtension;
use Zroger\Feather\Console\Formatter\OutputFormatter;
use Zroger\Feather\DependencyInjection\FeatherYamlFileLoader;

class Application extends BaseApplication
{
    private $logBuffer = array();
    private $logger;

    protected $runningCommand;

    const VERSION = "@git_version@";

    public function __construct()
    {
        parent::__construct('Feather', self::VERSION);
        $this->add(new Command\RunCommand());
        $this->add(new Command\SelfUpdateCommand());
    }

    /**
     * Get the DI container instance.
     * @return ContainerInterface The DI container instance.
     */
    public function getContainer()
    {
        if (!isset($this->container)) {
            $container = new ContainerBuilder();
            $container->registerExtension(new FeatherExtension($this->getBasePath()));

            // Static defaults.
            $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__)));
            $loader->load('feather.dist.yml');

            // Try to load config values from project-specific config file.
            try {
                $loader = new FeatherYamlFileLoader($container, new FileLocator($this->getBasePath()));
                $loader->load('feather.yml');
            } catch (\InvalidArgumentException $e) {
                // feather.yml is optional.
            }

            $this->container = $container;
        }
        return $this->container;
    }

    /**
     * Compile the container, optionally setting additional parameters via the
     * $values array.
     *
     * @param  array  $values An array of config values as expected in the feather
     *                section of the feather.yml file.
     * @return ContainerInterface The compiled container.
     */
    public function compileContainer(array $values = array())
    {
        $this->getContainer()->loadFromExtension('feather', $values);

        // Set contextual parameters.
        $this->getContainer()->setParameter('feather.paths.base', $this->getBasePath());

        $this->getContainer()->compile();

        return $this->getContainer();
    }

    protected function getBasePath()
    {
        if ($file = $this->locateProjectConfigFile()) {
            $basePath = dirname($file);
        } else {
            $basePath = posix_getcwd();
        }
        return $basePath;
    }

    /**
     * Traverse up from the current directory looking for a feather.yml file.
     *
     * @return string|false The file path of feather.yml or false if not found.
     */
    protected function locateProjectConfigFile()
    {
        $dirs = array();
        $parts = explode('/', posix_getcwd());
        while (!empty($parts)) {
            $dirs[] = join('/', $parts);
            array_pop($parts);
        }

        // Try to load config values from project-specific config file.
        try {
            $locator = new FileLocator($dirs);
            $file = $locator->locate('feather.yml');
            return $file;
        } catch (\InvalidArgumentException $e) {
            // feather.yml is optional.
        }

        return false;
    }

    /**
     * Method overridden to add output customizations and use the output object
     * for application logging.
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $output) {
            $output = new ConsoleOutput();
            $output->setFormatter(new OutputFormatter($output->isDecorated()));
        }

        $this->logger = $output;

        return parent::run($input, $output);
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        // Compile container here before commands are run.
        $config = array();
        if ($port = $input->getParameterOption(array('--port', '-p'))) {
            $config['port'] = intval($port);
        }
        if ($root = $input->getParameterOption(array('--root', '-r'))) {
            $config['document_root'] = realpath($root);
        }
        $this->compileContainer($config);

        parent::doRun($input, $output);
    }


    /**
     * Rudimentary logger.
     */
    public function log($message, $level = "info")
    {
        $this->logBuffer[] = array('message' => $message, 'level' => $level);

        if (isset($this->logger)) {
            $formatter = $this->getHelperSet()->get('formatter');
            while ($log = array_shift($this->logBuffer)) {
                $line = $formatter->formatSection($log['level'], $log['message'], $log['level']);
                $this->logger->writeln($line);
            }
        }
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOptions(
            array(
                new InputOption('--port', '-p', InputOption::VALUE_REQUIRED, 'Specify the port to listen on.'),
                new InputOption('--root', '-r', InputOption::VALUE_REQUIRED, 'Specify the document root to use.'),
            )
        );
        return $definition;
    }
}
