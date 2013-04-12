<?php

namespace Zroger\Feather;

use Symfony\Component\Yaml\Yaml;

class ApacheConfig {
  protected $template, $port, $root, $tmp_dir, $error_log;

  public function __construct() {
    $this->port = '8080';
    $this->root = posix_getcwd();
  }

  public function getTemplate() {
    return $this->template;
  }

  public function setTemplate($template) {
    $this->template = $template;
  }

  public function getPort() {
    return $this->port;
  }

  public function setPort($port) {
    $this->port = $port;
  }

  public function getRoot() {
    return $this->root;
  }

  public function setRoot($root) {
    $this->root = $root;
  }

  public function getTmpDir() {
    if (!isset($this->tmp_dir)) {
      $dir = sprintf("%s/feather.%s", sys_get_temp_dir(), getmypid());
      if (!is_dir($dir)) {
          mkdir($dir);
      }
      $this->tmp_dir = $dir;
    }
    return $this->tmp_dir;
  }

  public function getErrorLog() {
    if (!isset($this->error_log)) {
      $this->error_log = new ErrorLog($this->getTmpDir() . '/error_log');
    }
    return $this->error_log;
  }

  public function loadYaml($file) {
    $data = Yaml::parse($file);

    if (isset($data['root'])) {
      $this->setRoot(dirname($file) . '/' . $data['root']);
    }

    if (isset($data['port'])) {
      $this->setPort($data['port']);
    }
  }

  public function toFile() {
    $loader = new \Twig_Loader_Filesystem(dirname(__FILE__) . "/templates");
    $twig = new \Twig_Environment($loader);

    $rendered = $twig->render('drupal.twig', array(
      'port' => $this->getPort(),
      'root' => $this->getRoot(),
      'tmp_dir' => $this->getTmpDir(),
      'error_log' => $this->getErrorLog()->getFilename(),
    ));
    $conf_file = $this->getTmpDir() . '/httpd.conf';
    file_put_contents($conf_file, $rendered);
    return $conf_file;
  }
}
