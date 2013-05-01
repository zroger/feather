<?php

namespace Zroger\Feather\Log;

class FileReader implements LogReaderInterface
{
    /**
     * The internal file object.
     * @var \SplFileObject
     */
    protected $file;

    /**
     * The internal cursor position of the file object.
     * @var int
     */
    protected $cursor;

    /**
     * The path to the file.
     * @var string
     */
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Get the next line from the log.
     *
     * @return Line The next message from the log as a Line object.
     */
    public function nextLine()
    {
        // Always seek back to the cursor to reset eof();
        $this->getFile()->fseek($this->cursor);
        if (!$this->getFile()->eof()) {
            if ($text = $this->getFile()->fgets()) {
                $this->cursor = $this->getFile()->ftell();
                return $this->parse($text);
            }
        }
        return false;
    }

    /**
     * Get all remaining lines from the log.
     *
     * @return array An array of the Line objects.
     */
    public function allLines()
    {
        $lines = array();
        while ($line = $this->nextLine()) {
            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * Get an SplFileObject representing the file to be read.
     *
     * @return \SplFileObject The log file object.
     */
    protected function getFile()
    {
        if (!isset($this->file)) {
            $this->file = new \SplFileObject($this->path, 'r');
            $this->file->fseek(0, SEEK_END);
            $this->cursor = $this->file->ftell();
        }
        return $this->file;
    }

    /**
     * Parse a line of input.
     *
     * @param  string $input A line of input as read from the log file.
     * @return Line          A Line object populated with values parsed from the input string.
     */
    protected function parse($input)
    {
        $rx = "/\[([^\]]+)\] \[([^\]]+)\] (.*)/";
        $input = trim($input);
        if (preg_match($rx, $input, $matches)) {
            return new Line($matches[3], $matches[1], $matches[2]);
        } else {
            return new Line($input, null, 'info');
        }
    }
}
