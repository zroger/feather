<?php

namespace Zroger\Feather\Log;

class Watcher
{
    /**
     * Array of LogReaderInterface objects.
     * @var array
     */
    protected $readers = array();

    /**
     * Add a reader to the watchlist.
     *
     * @param string                     $label  A label used to refer to this reader instance.
     * @param LogReaderInterface $reader An instantiated reader object.
     */
    public function addReader($label, LogReaderInterface $reader)
    {
        $this->readers[$label] = $reader;
        return $this;
    }

    /**
     * Remove the reader instance specified by the label.
     *
     * @param  string $label A label of the reader instance to be removed.
     * @return Watcher       Returns $this for method chaining.
     */
    public function removeReader($label)
    {
        unset($this->readers[$label]);
        return $this;
    }

    /**
     * Continuously watch for new log messages from all readers.  This will run
     * indefinitely so make sure to provide a way out.
     *
     * @param  callable  $callable  A callable that receives a single Line object.
     * @param  integer   $uinterval Interval in microseconds to sleep between iterations.
     */
    public function watch($callable, $uinterval = 200000)
    {
        if (empty($this->readers)) {
            throw new \RuntimeException('The watchlist is empty, nothing to watch.');
        }

        while (true) {
            foreach ($this->readers as $label => $reader) {
                foreach ($reader->allLines() as $line) {
                    $callable($label, $line);
                }
            }
            usleep($uinterval);
        }
    }
}
