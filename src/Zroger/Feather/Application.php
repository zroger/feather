<?php

namespace Zroger\Feather;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Zroger\Feather\Config\AppConfig;
use Zroger\Feather\DependencyInjection\Compiler\HttpdConfCompilerPass;
use Zroger\Feather\DependencyInjection\FeatherExtension;
use Zroger\Feather\Console\Formatter\OutputFormatter;

class Application extends BaseApplication {
  private $logBuffer = array(), $logger;

  const VERSION = "dev";

  public function __construct() {
    parent::__construct('Feather', self::VERSION);

    if ($file = $this->locateProjectConfigFile()) {
      $basePath = dirname($file);
    }
    else {
      $basePath = posix_getcwd();
    }

    $this->container = new ContainerBuilder();
    $this->container->registerExtension(new FeatherExtension($basePath));

    // Static defaults.
    $loader = new YamlFileLoader($this->container, new FileLocator(__DIR__));
    $loader->load('feather.dist.yml');

    // Try to load config values from project-specific config file.
    try {
      $loader = new YamlFileLoader($this->container, new FileLocator($basePath));
      $loader->load('feather.yml');
    }
    catch (\InvalidArgumentException $e) {}

    $this->add(new Command\RunCommand());
  }

  /**
   * Compile the container, optionally setting additional parameters via the
   * $values array.
   *
   * @param  array  $values An array of config values as expected in the feather
   *                section of the feather.yml file.
   * @return ContainerInterface The compiled container.
   */
  public function compileContainer(array $values = array()) {
    $this->container->loadFromExtension('feather', $values);

    // Set contextual parameters.
    $this->container->setParameter('feather.paths.base', $this->getBasePath());

    $this->container->compile();

    return $this->container;
  }

  protected function getBasePath() {
    if ($file = $this->locateProjectConfigFile()) {
      $basePath = dirname($file);
    }
    else {
      $basePath = posix_getcwd();
    }
    return $basePath;
  }

  /**
   * Traverse up from the current directory looking for a feather.yml file.
   *
   * @return string|false The file path of feather.yml or false if not found.
   */
  protected function locateProjectConfigFile() {
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
    }
    catch (\InvalidArgumentException $e) {}

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
   * Rudimentary logger.
   */
  public function log($message, $level = "info") {
    $this->logBuffer[] = array('message' => $message, 'level' => $level);

    if (isset($this->logger)) {
      $formatter = $this->getHelperSet()->get('formatter');
      while($log = array_shift($this->logBuffer)) {
        $line = $formatter->formatSection($log['level'], $log['message'], $log['level']);
        $this->logger->writeln($line);
      }
    }
  }
}
