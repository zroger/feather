<?php

namespace Zroger\Feather;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

use Zroger\Feather\Config\AppConfig;

class Application extends BaseApplication {
  private $serverRoot, $projectRoot, $logBuffer = array(), $logger, $config, $configs = array();

  public function __construct($version) {
    parent::__construct('Feather', $version);
    $this->add(new Command\RunCommand());

    // Load default config values.
    $this->loadYamlConfig(dirname(__FILE__) .'/feather.dist.yml');

    // Try to load config values from project-specific config file.
    $locator = new FileLocator($this->getProjectRoot());
    if ($yamlFile = $locator->locate('feather.yml', null, true)) {
      $this->loadYamlConfig($yamlFile);
    }
  }

  protected function loadYamlConfig($filepath) {
    $this->log("Loading config from {$filepath}");
    $config = Yaml::parse($filepath);

    // Paths declared in a YAML file are relative to the file.
    $paths = array('root');
    foreach ($paths as $path) {
      if (isset($config[$path]) && (strpos($config[$path], '/') !== 0)) {
        $config[$path] = dirname($filepath) . '/' . $config[$path];
      }
    }

    $this->setConfig($config);
  }

  protected function customizeOutput(OutputInterface $output) {
    $style = new OutputFormatterStyle('red', null);
    $output->getFormatter()->setStyle('error', $style);

    $style = new OutputFormatterStyle('yellow', null);
    $output->getFormatter()->setStyle('debug', $style);
    $output->getFormatter()->setStyle('notice', $style);

    $style = new OutputFormatterStyle('white', null);
    $output->getFormatter()->setStyle('access', $style);

    return $output;
  }

  /**
   * Method overridden to add output customizations and use the output object
   * for application logging.
   */
  public function run(InputInterface $input = null, OutputInterface $output = null) {
    if (null === $output) {
      $output = new ConsoleOutput();
    }

    $this->logger = $output = $this->customizeOutput($output);

    return parent::run($input, $output);
  }


  /**
   * Get the application root, identified by the presence of a feather.yml file,
   * or a .feather directory.  This function traverses up the directory tree to
   * find a suitable candidate matching these criteria, with a fallback to the
   * current working directory.
   *
   * @return string The path to the application root.
   */
  public function getProjectRoot() {
    if (!isset($this->projectRoot)) {
      $dir = posix_getcwd();
      do {
        if (file_exists("$dir/feather.yml") || is_dir("$dir/.feather")) {
          $this->projectRoot = $dir;
          return $this->projectRoot;
        }
        $dir = dirname($dir);
      } while ($dir != '/');

      // Use the cwd if we reach the filesystem root.
      $this->projectRoot = posix_getcwd();
    }
    return $this->projectRoot;
  }

  /**
   * Get the path the the project cache (.feather) directory.
   *
   * @return string The path to the project cache directory.
   */
  public function getServerRoot() {
    if (!isset($this->serverRoot)) {
      $dir = $this->getProjectRoot() . '/.feather';
      if (!is_dir($dir)) {
        if (!mkdir($dir)) {
          throw new \RuntimeException("Unable to create cache directory {$dir}");
        }
        // Nothing in this folder should be committed.
        file_put_contents($dir . "/.gitignore", "*\n");
      }
      $this->serverRoot = $dir;
    }
    return $this->serverRoot;
  }

  /**
   * Get the app config.
   *
   * @return array The processed app config array.
   */
  public function getConfig() {
    if (!isset($this->config)) {
      $processor = new Processor();
      $configuration = new AppConfig;
      $this->config = $processor->processConfiguration(
          $configuration,
          $this->configs);
    }
    return $this->config;
  }

  /**
   * Set the app config with an array of config options.  This doesn't directly
   * set the config, but rather adds the passed in config array to the collection
   * of configs that will ultimately be processed by the config processor. This
   * will throw an exception if the config is frozen.
   *
   * @param array $config Array of new config values.
   */
  public function setConfig($config) {
    if ($this->configIsFrozen()) {
      throw new \RuntimeException("Unable to set App config because it is already frozen.");
    }
    $this->configs[] = $config;
  }

  /**
   * The app config is considered frozen once it has been processed.
   *
   * @return boolean
   */
  public function configIsFrozen() {
    return isset($this->config);
  }

  /**
   * Rudimentary logger.
   */
  public function log($message, $level = "info") {
    $this->logBuffer[] = array('message' => $message, 'level' => $level);

    if (isset($this->logger)) {
      while($log = array_shift($this->logBuffer)) {
        $this->logger->writeln(sprintf('<%s>%s</%s>', $log['level'], $log['message'], $log['level']));
      }
    }
  }
}
