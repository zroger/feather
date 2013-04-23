<?php

namespace Zroger\Feather\Log;

interface LogReaderInterface
{
    /**
     * Get the next line from the log.
     * @return Line The next message from the log as a Line instance.
     */
    public function nextLine();

    /**
     * Get all remaining lines from the log.
     * @return array An array of the Line instances.
     */
    public function allLines();
}
