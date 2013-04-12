<?php

namespace Zroger\Feather;

class ErrorLog {
  private $filename, $handle;

  public function __construct($filename) {
    if(!posix_mkfifo($filename, 0666)){
      throw new \RuntimeException("Error creating pipe for error log ($filename).");
    }

    $this->filename = $filename;
  }

  public function getFilename() {
    return $this->filename;
  }

  public function getHandle() {
    if (!isset($this->handle) || !is_resource($this->handle)) {
      $this->handle = fopen($this->getFilename(), 'r+');
      stream_set_blocking($this->handle,false);
    }
    return $this->handle;
  }

  public function read() {
    $rx = "/\[([^\]]+)\] \[([^\]]+)\] (.*)/";
    if ($input = trim(fgets($this->getHandle()))) {
      $line = new \stdClass();
      $line->message = $input;
      if (preg_match($rx, $input, $matches)) {
        $line->date = $matches[1];
        $line->type = $matches[2];
        $line->message = $matches[3];
      }
      return $line;
    }
    return false;
  }

}
