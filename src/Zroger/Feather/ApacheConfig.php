<?php

namespace Zroger\Feather;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\FileLocator;

class ApacheConfig {
  protected $template, $port, $root, $serverRoot, $error_log;

  public function __construct($serverRoot, $appConfig = array()) {
    $this->serverRoot = $serverRoot;
    $this->appConfig = array_replace(array(
      'port' => 8080,
      'root' => posix_getcwd(),
      'template' => 'drupal.twig',
    ), $appConfig);
  }

  public function getTemplate() {
    return $this->template ?: $this->appConfig['template'];
  }

  public function setTemplate($template) {
    $this->template = $template;
  }

  public function getPort() {
    return $this->port ?: $this->appConfig['port'];
  }

  public function setPort($port) {
    $this->port = $port;
  }

  public function getRoot() {
    return $this->root ?: $this->appConfig['root'];
  }

  public function setRoot($root) {
    $this->root = $root;
  }

  public function getServerRoot() {
    return $this->serverRoot;
  }

  public function getErrorLog() {
    if (!isset($this->error_log)) {
      $this->error_log = new LogReader($this->getServerRoot() . '/error_log');
    }
    return $this->error_log;
  }

  public function getModules() {
    $modules = array();
    foreach ($this->appConfig['modules'] as $module => $filename) {
      $modules[$module] = $this->locateModule($filename);
    }
    return $modules;
  }

  public function toFile() {
    $loader = new \Twig_Loader_Filesystem(dirname(__FILE__) . "/templates");
    $twig = new \Twig_Environment($loader);

    $vars = array(
      'port' => $this->getPort(),
      'root' => $this->getRoot(),
      'server_root' => $this->getServerRoot(),
      'error_log' => $this->getErrorLog()->getFilename(),
      'modules' => $this->getModules(),
    );
    $rendered = $twig->render($this->getTemplate(), $vars);
    $conf_file = $this->getServerRoot() . '/httpd.conf';
    file_put_contents($conf_file, $rendered);
    return $conf_file;
  }

  protected function getModuleDirectories() {
    if (!isset($this->moduleDirectories)) {
      $dirs = array();

      // PHP 5.3 from josegonzalez/php/php53 homebrew tap.
      if (exec('which brew')) {
        if ($php_dir = exec('brew --prefix php53')) {
          $dirs[] = $php_dir . '/libexec/apache2';
        }
      }

      // osx default apache.
      $dirs[] = '/usr/libexec/apache2';

      $this->moduleDirectories = $dirs;
    }
    return $this->moduleDirectories;
  }

  protected function locateModule($filename) {
    $locator = new FileLocator($this->getModuleDirectories());
    return $locator->locate($filename, null, true);
  }
}
