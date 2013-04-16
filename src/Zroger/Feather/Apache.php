<?php

namespace Zroger\Feather;

use Symfony\Component\Process\Process;

class Apache {

  public function __construct($feather_dir, $log_level = 'info') {
    $this->configDir = $feather_dir;
    $this->configFile = $feather_dir . '/httpd.conf';
    $this->logLevel = $log_level;
  }

  protected function buildCommandString($action, $extras = '') {
    return sprintf('httpd -f "%s" -k "%s" %s', $this->configFile, $action, $extras);
  }

  /**
   * For some reason, starting httpd hangs while using $process->wait.  This is
   * just a much simplified version.
   */
  protected function wait($process, $timeout = 30) {
    $timeout = time() + 30;
    while ($process->isRunning()) {
      if (time() >= $timeout) {
        throw \RuntimeException('timeout');
      }
      usleep(200);
    }

    return;
  }

  public function start() {
    $process = new Process($this->buildCommandString('start', "-e {$this->logLevel}"));
    $process->start();
    $this->wait($process);

    if (!$process->isSuccessful()) {
      throw new \RuntimeException(sprintf("Apache failed to start with the following error:\n%s", $process->getErrorOutput()));
    }

    return $process;
  }

  public function stop() {
    $process = new Process($this->buildCommandString('stop'));
    $process->start();
    $process->wait();
    return $process->isSuccessful();
  }

  public function isRunning() {
    $pidfile = $this->configDir . '/httpd.pid';
    if (file_exists($pidfile) && ($pid = file_get_contents($pidfile))) {
      $pid = trim($pid);
      $lockfile = $this->configDir . '/accept.lock.'. $pid;
      if (file_exists($lockfile)) {
        return true;
      }
    }

    return false;
  }
}
